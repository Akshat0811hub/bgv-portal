<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ImportPortalBackup extends Command
{
    protected $signature = 'portal:import-backup {path : Private JSON export path} {--validate-only : Validate without writing records or files}';

    protected $description = 'Import a complete portal export into an empty database and restore private documents';

    private const TABLES = ['client_organizations', 'users', 'checks', 'cases', 'case_checks', 'upload_tokens', 'settings', 'documents'];

    public function handle(): int
    {
        try {
            $path = $this->argument('path');
            if (! is_file($path) || ! is_readable($path)) {
                throw new RuntimeException('The private export file is not readable.');
            }
            $backup = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (($backup['format'] ?? null) !== 'orbit-bgv-transfer-v1') {
                throw new RuntimeException('Unsupported portal export format.');
            }
            $tables = $backup['tables'] ?? [];
            foreach (self::TABLES as $table) {
                if (! isset($tables[$table]) || ! is_array($tables[$table]) || ! array_is_list($tables[$table])) {
                    throw new RuntimeException('The export is missing a required table.');
                }
            }
            $documentHashes = [];
            foreach ($tables['documents'] as $document) {
                $documentPath = $document['stored_path'] ?? '';
                if (! is_string($documentPath) || $documentPath === '' || str_starts_with($documentPath, '/') || str_contains($documentPath, ':') || str_contains($documentPath, '\\') || str_contains($documentPath, "\0") || array_intersect(['.', '..'], explode('/', $documentPath))) {
                    throw new RuntimeException('An attachment has an unsafe storage path.');
                }
                $content = $document['file_content'] ?? null;
                $bytes = is_string($content) ? base64_decode($content, true) : false;
                if ($bytes === false || ! isset($document['file_size']) || strlen($bytes) !== (int) $document['file_size']) {
                    throw new RuntimeException('An attachment is missing its complete original content.');
                }
                $hash = hash('sha256', $bytes);
                if (isset($documentHashes[$documentPath]) && $documentHashes[$documentPath] !== $hash) {
                    throw new RuntimeException('Two attachments have conflicting storage paths.');
                }
                $documentHashes[$documentPath] = $hash;
            }
            $this->info('Export validated: '.count($tables['users']).' users, '.count($tables['cases']).' cases, '.count($tables['documents']).' complete documents.');
            if ($this->option('validate-only')) {
                return self::SUCCESS;
            }
            if (DB::getDriverName() === 'pgsql') {
                throw new RuntimeException('Use this migration command on the new MySQL database, not the PostgreSQL source.');
            }
            foreach (self::TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException('Run the application migrations before importing.');
                }
                if (DB::table($table)->exists()) {
                    throw new RuntimeException('Import refused: the destination database must be empty. Existing data was preserved.');
                }
                $columns = Schema::getColumnListing($table);
                foreach ($tables[$table] as $row) {
                    if (! is_array($row) || array_diff(array_keys($row), $columns)) {
                        throw new RuntimeException('The export schema does not match the application.');
                    }
                }
            }
            $disk = Storage::disk('local');
            // Check all collisions before creating files or inserting records.
            foreach ($documentHashes as $documentPath => $hash) {
                if ($disk->exists($documentPath) && hash('sha256', $disk->get($documentPath)) !== $hash) {
                    throw new RuntimeException('Import refused: an existing private file has different contents.');
                }
            }
            DB::transaction(function () use ($tables, $disk, $documentHashes) {
                foreach (self::TABLES as $table) {
                    foreach ($tables[$table] as $row) {
                        foreach ($row as $column => &$value) {
                            if (is_array($value)) {
                                $value = json_encode($value, JSON_THROW_ON_ERROR);
                            } elseif (is_bool($value)) {
                                $value = (int) $value;
                            } elseif ($value !== null && str_ends_with($column, '_at')) {
                                $value = CarbonImmutable::parse($value)->format('Y-m-d H:i:s');
                            }
                        }
                        unset($value);
                        if ($table === 'users') {
                            $row['remember_token'] = null;
                        }
                        if ($table === 'documents') {
                            $documentPath = $row['stored_path'];
                            if (! $disk->exists($documentPath) && ! $disk->put($documentPath, base64_decode($row['file_content'], true))) {
                                throw new RuntimeException('Could not restore a private attachment.');
                            }
                            if (hash('sha256', $disk->get($documentPath)) !== $documentHashes[$documentPath]) {
                                throw new RuntimeException('An attachment failed verification after restoration.');
                            }
                        }
                        // Raw inserts preserve primary keys and existing password hashes.
                        DB::table($table)->insert($row);
                    }
                    if (DB::table($table)->count() !== count($tables[$table])) {
                        throw new RuntimeException('An imported table failed its record count check.');
                    }
                }
            });
            $this->info('Import committed. Original IDs, password hashes, check data and document bytes were preserved. Sessions were not imported.');

            return self::SUCCESS;
        } catch (RuntimeException $error) {
            // Never print database queries, credentials, or candidate data in CLI output.
            $safeMessages = ['The private export file is not readable.', 'Unsupported portal export format.', 'The export is missing a required table.', 'An attachment has an unsafe storage path.', 'An attachment is missing its complete original content.', 'Two attachments have conflicting storage paths.', 'Use this migration command on the new MySQL database, not the PostgreSQL source.', 'Run the application migrations before importing.', 'Import refused: the destination database must be empty. Existing data was preserved.', 'The export schema does not match the application.', 'Import refused: an existing private file has different contents.', 'Could not restore a private attachment.', 'An attachment failed verification after restoration.', 'An imported table failed its record count check.'];
            $this->error(in_array($error->getMessage(), $safeMessages, true) ? $error->getMessage() : 'Import failed; no database changes were committed. Check schema, database access and storage permissions.');
        } catch (Throwable) {
            $this->error('Import failed; no database changes were committed. Check the export format, schema and storage permissions.');
        }

        return self::FAILURE;
    }
}
