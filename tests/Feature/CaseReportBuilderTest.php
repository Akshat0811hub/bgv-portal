<?php

namespace Tests\Feature;

use App\Exceptions\ReportDocumentException;
use App\Models\CaseCheck;
use App\Models\Cases;
use App\Models\ClientOrganization;
use App\Models\Document;
use App\Models\User;
use App\Services\CaseReportBuilder;
use App\Services\ReportDocumentConverter;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfStream;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfReader\PdfReader;
use Tests\TestCase;

class CaseReportBuilderTest extends TestCase
{
    use RefreshDatabase;

    private Cases $case;

    private CaseCheck $education;

    private CaseCheck $employment;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $organization = ClientOrganization::create(['name' => 'Report Test Organization']);
        $admin = User::create([
            'name' => 'Report Admin', 'email' => 'report-admin@example.test', 'password' => 'password123',
            'role' => 'admin', 'is_verified' => true,
        ]);
        $this->case = Cases::create([
            'case_number' => 'OV-REPORT-TEST', 'client_organization_id' => $organization->id,
            'created_by' => $admin->id, 'candidate_name' => 'Report Candidate',
            'candidate_email' => 'candidate@example.test', 'status' => 'Pending', 'case_start_date' => today(),
        ]);
        $this->education = $this->case->checks()->create([
            'check_type' => 'education_verification', 'status' => 'Clear', 'completed_at' => now(),
            'custom_schema' => ['_self' => [['name' => 'degree', 'label' => 'Degree', 'type' => 'text']]],
            'form_data' => ['_self' => ['degree' => 'Bachelor of Science']],
            'verified_data' => ['_self' => ['degree' => 'Bachelor of Science']],
            'comments' => 'University records matched.',
        ]);
        $this->employment = $this->case->checks()->create([
            'check_type' => 'employment_verification', 'status' => 'In Progress',
            'custom_schema' => ['Employer 1' => [['name' => 'company', 'label' => 'Company', 'type' => 'text']]],
            'form_data' => ['Employer 1' => ['company' => 'Example Company']],
        ]);
    }

    public function test_every_source_page_follows_its_check_and_includes_every_upload_role(): void
    {
        $baseline = $this->pages(app(CaseReportBuilder::class)->build($this->case));
        $this->document($this->education, 'candidate-degree.pdf', $this->fixturePdf('CANDIDATE-DEGREE', 2), 'candidate');
        $this->document($this->education, 'client-degree.pdf', $this->fixturePdf('CLIENT-DEGREE'), 'client');
        $this->document($this->education, 'admin-degree.pdf', $this->fixturePdf('ADMIN-DEGREE'), 'admin');
        $this->document($this->employment, 'employment-proof.pdf', $this->fixturePdf('EMPLOYMENT-PROOF', 2, true), 'candidate');
        $this->document(null, 'general-proof.pdf', $this->fixturePdf('GENERAL-PROOF'), 'client');

        $pages = $this->pages(app(CaseReportBuilder::class)->build($this->case));
        $this->assertCount(count($baseline) + 7, $pages);
        $proofPages = array_values(array_filter($pages, fn ($page) => str_contains($page, 'Document page ')));
        $this->assertCount(7, $proofPages);
        $this->assertStringContainsString('CANDIDATE-DEGREE-1', $proofPages[0]);
        $this->assertStringContainsString('CANDIDATE-DEGREE-2', $proofPages[1]);
        $this->assertStringContainsString('CLIENT-DEGREE-1', $proofPages[2]);
        $this->assertStringContainsString('ADMIN-DEGREE-1', $proofPages[3]);
        $this->assertStringContainsString('EMPLOYMENT-PROOF-1', $proofPages[4]);
        $this->assertStringContainsString('EMPLOYMENT-PROOF-2', $proofPages[5]);
        $this->assertStringContainsString('GENERAL-PROOF-1', $proofPages[6]);
        $this->assertStringContainsString('Uploaded by Candidate', $proofPages[0]);
        $this->assertStringContainsString('Uploaded by Client', $proofPages[2]);
        $this->assertStringContainsString('Uploaded by Admin', $proofPages[3]);
        $this->assertStringContainsString('Document page 2 of 2', $proofPages[1]);

        $educationProof = array_search($proofPages[3], $pages, true);
        $employmentProof = array_search($proofPages[4], $pages, true);
        $this->assertGreaterThan($educationProof + 1, $employmentProof, 'Employment findings must appear between the checks\' evidence.');
        foreach ($pages as $index => $page) {
            $this->assertStringContainsString('Page '.($index + 1).'/'.count($pages), $page);
            $this->assertStringNotContainsString('{nb}', $page);
        }
    }

    public function test_missing_disk_document_is_restored_from_database_content(): void
    {
        $this->document($this->education, 'database-proof.pdf', $this->fixturePdf('DATABASE-PROOF', 2), 'candidate', true);
        $pages = $this->pages(app(CaseReportBuilder::class)->build($this->case));
        $proofPages = array_values(array_filter($pages, fn ($page) => str_contains($page, 'database-proof.pdf')));
        $this->assertCount(2, $proofPages);
        $this->assertStringContainsString('DATABASE-PROOF-1', $proofPages[0]);
        $this->assertStringContainsString('DATABASE-PROOF-2', $proofPages[1]);
    }

    public function test_images_are_rendered_as_evidence_in_the_same_pdf(): void
    {
        $image = imagecreatetruecolor(120, 80);
        imagefill($image, 0, 0, imagecolorallocate($image, 12, 148, 136));
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        $this->document($this->education, 'degree-photo.png', $bytes, 'client');

        $pages = $this->pages(app(CaseReportBuilder::class)->build($this->case));
        $proofPages = array_values(array_filter($pages, fn ($page) => str_contains($page, 'degree-photo.png')));
        $this->assertCount(1, $proofPages);
        $this->assertStringContainsString('Uploaded by Client', $proofPages[0]);
        $this->assertStringContainsString('/I', $proofPages[0], 'An image drawing must be present in the imported page.');
    }

    public function test_office_conversion_keeps_every_returned_pdf_page(): void
    {
        $bytes = 'complete binary office document';
        $this->document($this->employment, 'employment-history.docx', $bytes, 'admin');
        $this->mock(ReportDocumentConverter::class, function ($mock) use ($bytes) {
            $mock->shouldReceive('convert')->once()->with($bytes, 'employment-history.docx')
                ->andReturn($this->fixturePdf('OFFICE-PAGE', 3));
        });

        $pages = $this->pages(app(CaseReportBuilder::class)->build($this->case));
        $proofPages = array_values(array_filter($pages, fn ($page) => str_contains($page, 'employment-history.docx')));
        $this->assertCount(3, $proofPages);
        foreach ($proofPages as $index => $page) {
            $this->assertStringContainsString('OFFICE-PAGE-'.($index + 1), $page);
            $this->assertStringContainsString('Document page '.($index + 1).' of 3', $page);
        }
    }

    public function test_unavailable_document_fails_instead_of_returning_an_incomplete_report(): void
    {
        $this->document($this->education, 'missing-proof.pdf', '', 'candidate', true);
        $this->expectException(ReportDocumentException::class);
        $this->expectExceptionMessage('missing-proof.pdf');
        app(CaseReportBuilder::class)->build($this->case);
    }

    public function test_unreadable_pdf_cannot_silently_become_an_external_link(): void
    {
        $this->document($this->education, 'broken-proof.pdf', '%PDF-1.4 broken contents', 'candidate');
        $this->mock(ReportDocumentConverter::class, function ($mock) {
            $mock->shouldReceive('normalizePdf')->once()->andThrow(new \RuntimeException('Damaged PDF.'));
        });
        $this->expectException(ReportDocumentException::class);
        $this->expectExceptionMessage('No incomplete report was downloaded');
        app(CaseReportBuilder::class)->build($this->case);
    }

    public function test_pdf_normalization_preserves_every_page_before_importing(): void
    {
        $original = '%PDF-1.7 requires normalization';
        $this->document($this->education, 'modern-proof.pdf', $original, 'candidate');
        $this->mock(ReportDocumentConverter::class, function ($mock) use ($original) {
            $mock->shouldReceive('normalizePdf')->once()->with($original)->andReturn($this->fixturePdf('NORMALIZED', 2));
        });
        $pages = $this->pages(app(CaseReportBuilder::class)->build($this->case));
        $proofPages = array_values(array_filter($pages, fn ($page) => str_contains($page, 'modern-proof.pdf')));
        $this->assertCount(2, $proofPages);
        $this->assertStringContainsString('NORMALIZED-1', $proofPages[0]);
        $this->assertStringContainsString('NORMALIZED-2', $proofPages[1]);
    }

    private function document(?CaseCheck $check, string $name, string $bytes, string $role, bool $databaseOnly = false): Document
    {
        $path = 'cases/'.$this->case->id.'/'.bin2hex(random_bytes(6));
        if (! $databaseOnly) {
            Storage::disk('local')->put($path, $bytes);
        }

        return $this->case->documents()->create([
            'case_check_id' => $check?->id, 'original_name' => $name, 'stored_path' => $path,
            'file_size' => strlen($bytes), 'mime_type' => 'application/octet-stream',
            'uploaded_by_role' => $role, 'field_key' => '_file',
            'file_content' => $databaseOnly ? base64_encode($bytes) : null,
        ]);
    }

    private function fixturePdf(string $marker, int $count = 1, bool $landscape = false): string
    {
        $pdf = new FPDF;
        $pdf->SetCompression(false);
        for ($index = 1; $index <= $count; $index++) {
            $pdf->AddPage($landscape ? 'L' : 'P');
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(0, 10, $marker.'-'.$index);
        }

        return $pdf->Output('S');
    }

    /** Decode content of only the XObjects drawn on each page, not all shared resources. */
    private function pages(string $bytes): array
    {
        $parser = new PdfParser(StreamReader::createByString($bytes));
        $reader = new PdfReader($parser);
        $pages = [];
        for ($index = 1; $index <= $reader->getPageCount(); $index++) {
            $page = $reader->getPage($index);
            $pages[] = $this->drawnStreams($page->getContentStream(), $page->getAttribute('Resources'), $parser);
        }

        return $pages;
    }

    private function drawnStreams(string $content, mixed $resources, PdfParser $parser, int $depth = 0): string
    {
        if ($depth > 8 || $resources === null) {
            return $content;
        }
        $dictionary = PdfType::resolve($resources, $parser);
        if (! $dictionary instanceof PdfDictionary) {
            return $content;
        }
        $objects = PdfType::resolve(PdfDictionary::get($dictionary, 'XObject'), $parser);
        if (! $objects instanceof PdfDictionary) {
            return $content;
        }
        $result = $content;
        preg_match_all('/\/([A-Za-z0-9_.-]+)\s+Do\b/', $content, $matches);
        foreach ($matches[1] as $name) {
            $object = PdfType::resolve(PdfDictionary::get($objects, $name), $parser);
            if (! $object instanceof PdfStream) {
                continue;
            }
            $subtype = PdfDictionary::get($object->value, 'Subtype');
            if ($subtype instanceof PdfName && $subtype->value === 'Form') {
                $result .= "\n".$this->drawnStreams(
                    $object->getUnfilteredStream(), PdfDictionary::get($object->value, 'Resources'), $parser, $depth + 1,
                );
            }
        }

        return $result;
    }
}
