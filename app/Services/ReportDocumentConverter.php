<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Converts the entire office document, including its tables and embedded images.
 * A missing converter or failed conversion is an error, never a text-only fallback.
 */
class ReportDocumentConverter
{
    public const OFFICE_EXTENSIONS = [
        'doc', 'docx', 'odt', 'rtf', 'xls', 'xlsx', 'ods', 'ppt', 'pptx', 'odp', 'odg', 'odf',
        'fodt', 'fods', 'fodp',
    ];

    public function convert(string $bytes, string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! in_array($extension, self::OFFICE_EXTENSIONS, true)) {
            throw new RuntimeException('This office file format is not supported.');
        }
        if ($bytes === '') {
            throw new RuntimeException('The office document is empty.');
        }

        $driver = config('reports.converter', 'auto');
        if ($driver === 'gotenberg' || ($driver === 'auto' && config('reports.gotenberg.url'))) {
            return $this->requestGotenberg($bytes, 'document.'.$extension, '/forms/libreoffice/convert', [
                'pdfa' => 'PDF/A-1b',
                'exportFormFields' => 'false',
            ]);
        }
        if (! in_array($driver, ['auto', 'libreoffice'], true)) {
            throw new RuntimeException('The report document converter is not configured correctly.');
        }

        $binary = $this->findBinary('reports.libreoffice_binary', ['soffice', 'libreoffice']);
        if ($binary === null) {
            throw new RuntimeException('Office document conversion is not configured. Configure LibreOffice or a trusted Gotenberg service.');
        }

        return $this->withWorkspace(function (string $directory) use ($binary, $bytes, $extension): string {
            $source = $directory.'/document.'.$extension;
            $this->writePrivateFile($source, $bytes);
            $outputDirectory = $directory.'/output';
            if (! mkdir($outputDirectory, 0700)) {
                throw new RuntimeException('Unable to create the conversion output directory.');
            }
            $filter = match ($extension) {
                'xls', 'xlsx', 'ods', 'fods' => 'calc_pdf_Export',
                'ppt', 'pptx', 'odp', 'fodp' => 'impress_pdf_Export',
                'odg' => 'draw_pdf_Export',
                'odf' => 'math_pdf_Export',
                default => 'writer_pdf_Export',
            };
            // PDF/A-1 uses PDF 1.4, so the resulting pages can also be read by FPDI.
            $options = json_encode([
                'SelectPdfVersion' => ['type' => 'long', 'value' => '1'],
                'ExportFormFields' => ['type' => 'boolean', 'value' => 'false'],
            ], JSON_THROW_ON_ERROR);
            $result = Process::timeout($this->timeout())->path($directory)->run([
                $binary,
                '-env:UserInstallation='.$this->fileUri($directory.'/profile'),
                '--headless', '--nologo', '--nodefault', '--nofirststartwizard', '--norestore',
                '--convert-to', 'pdf:'.$filter.':'.$options,
                '--outdir', $outputDirectory,
                $source,
            ]);
            $pdf = $outputDirectory.'/document.pdf';
            // LibreOffice can return exit code zero without creating a PDF.
            if (! $result->successful() || ! is_file($pdf)) {
                throw new RuntimeException('The office document could not be converted. It may be damaged, password protected, or require a font missing from the converter.');
            }

            return $this->readPdf($pdf);
        });
    }

    /** Normalize compressed object/xref streams without dropping document pages. */
    public function normalizePdf(string $bytes): string
    {
        $this->assertPdf($bytes);
        $binary = $this->findBinary('reports.qpdf_binary', ['qpdf']);
        if ($binary !== null) {
            return $this->withWorkspace(function (string $directory) use ($binary, $bytes): string {
                $source = $directory.'/source.pdf';
                $destination = $directory.'/normalized.pdf';
                $this->writePrivateFile($source, $bytes);
                $result = Process::timeout($this->timeout())->path($directory)->run([
                    $binary, '--object-streams=disable', '--stream-data=preserve',
                    $source, $destination,
                ]);
                // qpdf exit code 3 reports recoverable warnings; the caller still parses every page.
                if (! in_array($result->exitCode(), [0, 3], true) || ! is_file($destination)) {
                    throw new RuntimeException('The PDF could not be normalized. Upload a readable, unprotected PDF.');
                }

                return $this->readPdf($destination);
            });
        }

        if (config('reports.gotenberg.url') && in_array(config('reports.converter', 'auto'), ['auto', 'gotenberg'], true)) {
            return $this->requestGotenberg($bytes, 'document.pdf', '/forms/pdfengines/convert', [
                'pdfa' => 'PDF/A-1b',
            ]);
        }

        throw new RuntimeException('This PDF requires normalization. Configure qpdf or a trusted Gotenberg service, or upload a PDF exported without encryption or compressed object streams.');
    }

    private function requestGotenberg(string $bytes, string $name, string $route, array $fields): string
    {
        $baseUrl = rtrim((string) config('reports.gotenberg.url'), '/');
        $parts = parse_url($baseUrl);
        if (! is_array($parts) || ! isset($parts['host']) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new RuntimeException('A valid trusted Gotenberg URL must be configured.');
        }

        $request = Http::connectTimeout(10)->timeout($this->timeout())
            ->accept('application/pdf')->withOptions([
                'allow_redirects' => false,
                'progress' => function ($downloadTotal, $downloaded): void {
                    if ($downloadTotal > $this->maxBytes() || $downloaded > $this->maxBytes()) {
                        throw new RuntimeException('The converted document exceeds the configured size limit.');
                    }
                },
            ]);
        if (config('reports.gotenberg.token')) {
            $request = $request->withToken(config('reports.gotenberg.token'));
        } elseif (config('reports.gotenberg.username') !== null) {
            $request = $request->withBasicAuth(
                config('reports.gotenberg.username'),
                (string) config('reports.gotenberg.password'),
            );
        }

        // A generic filename keeps personal details out of converter access logs.
        $response = $request->attach('files', $bytes, $name)->post($baseUrl.$route, $fields);
        if (! $response->successful()) {
            throw new RuntimeException('Document conversion failed (converter HTTP '.$response->status().'). Check the converter and upload a readable, unprotected document.');
        }
        $pdf = $response->body();
        $this->assertPdf($pdf);

        return $pdf;
    }

    private function findBinary(string $configKey, array $names): ?string
    {
        $configured = config($configKey);
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }
        $finder = new ExecutableFinder;
        foreach ($names as $name) {
            if (($binary = $finder->find($name)) !== null) {
                return $binary;
            }
        }

        if ($configKey === 'reports.libreoffice_binary' && PHP_OS_FAMILY === 'Windows') {
            foreach (['C:/Program Files/LibreOffice/program/soffice.exe', 'C:/Program Files (x86)/LibreOffice/program/soffice.exe'] as $binary) {
                if (is_file($binary)) {
                    return $binary;
                }
            }
        }

        return null;
    }

    private function withWorkspace(callable $callback): string
    {
        $directory = rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.'orbit-report-'.bin2hex(random_bytes(16));
        if (! mkdir($directory, 0700)) {
            throw new RuntimeException('Unable to create a private document conversion workspace.');
        }
        try {
            return $callback($directory);
        } finally {
            // Only remove this newly-created private workspace; never follow symlinks.
            $this->removeWorkspace($directory);
        }
    }

    private function removeWorkspace(string $directory): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($directory);
    }

    private function writePrivateFile(string $path, string $bytes): void
    {
        if (file_put_contents($path, $bytes) !== strlen($bytes)) {
            throw new RuntimeException('Unable to write the document conversion input.');
        }
        @chmod($path, 0600);
    }

    private function readPdf(string $path): string
    {
        $size = filesize($path);
        if ($size === false || $size > $this->maxBytes()) {
            throw new RuntimeException('The converted document exceeds the configured size limit.');
        }
        $bytes = file_get_contents($path);
        if (! is_string($bytes)) {
            throw new RuntimeException('Unable to read the converted document.');
        }
        $this->assertPdf($bytes);

        return $bytes;
    }

    private function assertPdf(string $bytes): void
    {
        if (! str_starts_with($bytes, '%PDF-') || strlen($bytes) > $this->maxBytes()) {
            throw new RuntimeException('The converter did not return a valid PDF within the configured size limit.');
        }
    }

    private function fileUri(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));

        return str_starts_with($path, '/') ? 'file://'.$encoded : 'file:///'.str_replace('%3A', ':', $encoded);
    }

    private function timeout(): int
    {
        return max(5, min(300, (int) config('reports.conversion_timeout', 90)));
    }

    private function maxBytes(): int
    {
        return max(1024, (int) config('reports.max_converted_bytes', 104857600));
    }
}
