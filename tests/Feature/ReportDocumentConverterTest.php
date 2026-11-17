<?php

namespace Tests\Feature;

use App\Services\ReportDocumentConverter;
use Illuminate\Http\Client\Request;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class ReportDocumentConverterTest extends TestCase
{
    private const PDF = "%PDF-1.4\nconverted test document";

    public function test_office_document_is_sent_only_to_the_configured_converter_with_authentication(): void
    {
        config([
            'reports.converter' => 'gotenberg',
            'reports.gotenberg.url' => 'https://converter.example.test',
            'reports.gotenberg.token' => 'test-token',
        ]);
        Http::fake(['converter.example.test/*' => Http::response(self::PDF, 200, ['Content-Type' => 'application/pdf'])]);

        $pdf = app(ReportDocumentConverter::class)->convert('whole office binary with images', 'Employment Evidence.DOCX');

        $this->assertSame(self::PDF, $pdf);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://converter.example.test/forms/libreoffice/convert'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request->hasFile('files', 'whole office binary with images', 'document.docx')
            && str_contains($request->body(), 'PDF/A-1b')
        );
        Http::assertSentCount(1);
    }

    public function test_unsupported_formats_are_rejected_without_a_remote_request(): void
    {
        Http::fake();
        try {
            app(ReportDocumentConverter::class)->convert('payload', 'attachment.exe');
            $this->fail('Unsupported format must fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('not supported', $exception->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_upstream_error_does_not_return_a_partial_or_text_only_document(): void
    {
        config(['reports.converter' => 'gotenberg', 'reports.gotenberg.url' => 'https://converter.example.test']);
        Http::fake(['converter.example.test/*' => Http::response('invalid protected file', 400)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('converter HTTP 400');
        app(ReportDocumentConverter::class)->convert('protected content', 'employment.odt');
    }

    public function test_non_pdf_response_is_rejected(): void
    {
        config(['reports.converter' => 'gotenberg', 'reports.gotenberg.url' => 'https://converter.example.test']);
        Http::fake(['converter.example.test/*' => Http::response('<html>login</html>', 200)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('valid PDF');
        app(ReportDocumentConverter::class)->convert('doc binary', 'reference.doc');
    }

    public function test_libreoffice_uses_isolated_workspace_and_cleans_it_after_success(): void
    {
        config(['reports.converter' => 'libreoffice', 'reports.libreoffice_binary' => '/test/soffice']);
        $workspace = null;
        Process::fake(function (PendingProcess $process) use (&$workspace) {
            $workspace = $process->path;
            $this->assertIsArray($process->command);
            $this->assertSame('/test/soffice', $process->command[0]);
            $this->assertContains('--headless', $process->command);
            $this->assertFileExists($workspace.'/document.odt');
            $this->assertSame('complete ODT bytes', file_get_contents($workspace.'/document.odt'));
            file_put_contents($workspace.'/output/document.pdf', self::PDF);

            return Process::result(exitCode: 0);
        });

        $this->assertSame(self::PDF, app(ReportDocumentConverter::class)->convert('complete ODT bytes', '../personal file.odt'));
        $this->assertNotNull($workspace);
        $this->assertDirectoryDoesNotExist($workspace);
    }

    public function test_libreoffice_cleans_workspace_when_it_returns_success_without_output(): void
    {
        config(['reports.converter' => 'libreoffice', 'reports.libreoffice_binary' => '/test/soffice']);
        $workspace = null;
        Process::fake(function (PendingProcess $process) use (&$workspace) {
            $workspace = $process->path;

            return Process::result(exitCode: 0);
        });

        try {
            app(ReportDocumentConverter::class)->convert('damaged doc', 'employment.doc');
            $this->fail('No PDF output must fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('could not be converted', $exception->getMessage());
        }
        $this->assertDirectoryDoesNotExist($workspace);
    }

    public function test_qpdf_normalizes_object_streams_without_shell_interpolation(): void
    {
        config(['reports.qpdf_binary' => '/test/qpdf']);
        $workspace = null;
        Process::fake(function (PendingProcess $process) use (&$workspace) {
            $workspace = $process->path;
            $this->assertIsArray($process->command);
            $this->assertContains('--object-streams=disable', $process->command);
            $this->assertSame(self::PDF, file_get_contents($workspace.'/source.pdf'));
            file_put_contents($workspace.'/normalized.pdf', self::PDF);

            return Process::result(exitCode: 3);
        });

        $this->assertSame(self::PDF, app(ReportDocumentConverter::class)->normalizePdf(self::PDF));
        $this->assertDirectoryDoesNotExist($workspace);
    }
}
