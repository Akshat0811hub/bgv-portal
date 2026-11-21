<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalBackupImportTest extends TestCase
{
    use RefreshDatabase;

    private array $exports = [];

    protected function tearDown(): void
    {
        foreach ($this->exports as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    private function export(array $changes = []): array
    {
        $password = Hash::make('migration-test-password');
        $tables = [
            'client_organizations' => [['id' => 3, 'name' => 'Migration Example']],
            'users' => [['id' => 7, 'name' => 'Migration Admin', 'email' => 'migration@example.test', 'password' => $password, 'role' => 'admin', 'is_verified' => true, 'remember_token' => 'old-session-token']],
            'checks' => [['id' => 5, 'name' => 'Employment', 'slug' => 'employment', 'schema' => ['sections' => []], 'active' => true]],
            'cases' => [['id' => 11, 'case_number' => 'OV-MIGRATION-11', 'candidate_name' => 'Example Candidate', 'candidate_email' => 'candidate@example.test', 'client_organization_id' => 3, 'created_by' => 7]],
            'case_checks' => [['id' => 13, 'case_id' => 11, 'check_type' => 'employment', 'custom_name' => 'Previous employment', 'form_data' => ['company' => 'Example Company'], 'custom_schema' => ['sections' => []]]],
            'upload_tokens' => [['id' => 19, 'case_id' => 11, 'token' => 'migration-candidate-link', 'used' => false, 'expires_at' => '2036-01-01T12:30:00']],
            'settings' => [],
            'documents' => [['id' => 23, 'case_id' => 11, 'case_check_id' => 13, 'uploaded_by' => 7, 'original_name' => 'proof.txt', 'stored_path' => 'cases/11/proof.txt', 'mime_type' => 'text/plain', 'file_size' => 5, 'file_content' => base64_encode('proof'), 'uploaded_by_role' => 'admin']],
        ];
        foreach ($changes as $table => $rows) {
            $tables[$table] = $rows;
        }
        $path = tempnam(sys_get_temp_dir(), 'orbit-import-test-');
        $this->exports[] = $path;
        file_put_contents($path, json_encode(['format' => 'orbit-bgv-transfer-v1', 'tables' => $tables], JSON_THROW_ON_ERROR));

        return [$path, $password];
    }

    public function test_import_restores_original_ids_passwords_customized_checks_and_file_bytes(): void
    {
        Storage::fake('local');
        [$path, $password] = $this->export();
        $this->artisan('portal:import-backup', ['path' => $path])->assertSuccessful();
        $this->assertDatabaseHas('users', ['id' => 7, 'password' => $password, 'remember_token' => null]);
        $this->assertDatabaseHas('cases', ['id' => 11, 'created_by' => 7]);
        $this->assertDatabaseHas('case_checks', ['id' => 13, 'custom_name' => 'Previous employment']);
        $this->assertDatabaseHas('documents', ['id' => 23, 'case_check_id' => 13, 'file_content' => base64_encode('proof')]);
        $this->assertSame('Example Company', json_decode(DB::table('case_checks')->value('form_data'), true)['company']);
        $this->assertSame('proof', Storage::disk('local')->get('cases/11/proof.txt'));
        $this->assertDatabaseCount('sessions', 0);
    }

    public function test_incomplete_attachment_prevents_all_record_and_file_writes(): void
    {
        Storage::fake('local');
        [$path] = $this->export(['documents' => [['stored_path' => 'cases/11/missing.pdf', 'file_size' => 50, 'file_content' => null]]]);
        $this->artisan('portal:import-backup', ['path' => $path])->assertFailed();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('cases', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_nonempty_destination_is_preserved_and_import_is_refused(): void
    {
        Storage::fake('local');
        DB::table('client_organizations')->insert(['id' => 1, 'name' => 'Existing customer']);
        [$path] = $this->export();
        $this->artisan('portal:import-backup', ['path' => $path])->assertFailed();
        $this->assertDatabaseHas('client_organizations', ['id' => 1, 'name' => 'Existing customer']);
        $this->assertDatabaseCount('users', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_foreign_key_failure_rolls_back_preceding_records(): void
    {
        Storage::fake('local');
        [$path] = $this->export(['cases' => [['id' => 11, 'case_number' => 'OV-BROKEN', 'candidate_name' => 'Example', 'candidate_email' => 'example@example.test', 'client_organization_id' => 999, 'created_by' => 7]]]);
        $this->artisan('portal:import-backup', ['path' => $path])->assertFailed();
        $this->assertDatabaseCount('client_organizations', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('checks', 0);
    }
}
