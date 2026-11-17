<?php

namespace Tests\Feature;

use App\Models\Cases;
use App\Models\ClientOrganization;
use App\Models\User;
use App\Services\CaseReportBuilder;
use App\Services\ReportDownload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): Cases
    {
        $org = ClientOrganization::create(['name' => 'Acme']);
        $client = User::create([
            'name' => 'HR', 'email' => 'hr@acme.test', 'password' => 'secret123',
            'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $org->id,
        ]);

        return Cases::create([
            'case_number' => Cases::generateCaseNumber(), 'candidate_name' => 'Candidate',
            'candidate_email' => 'candidate@example.test', 'created_by' => $client->id,
            'client_organization_id' => $org->id, 'status' => 'Pending', 'case_start_date' => today(),
        ]);
    }

    public function test_large_pdf_reassembles_without_missing_bytes_and_is_owned_by_requesting_user(): void
    {
        $case = $this->makeCase();
        $this->actingAs($case->createdBy);
        $pdf = '%PDF-1.4'.str_repeat('multi-page evidence', 180_000);
        config(['reports.chunked_downloads' => true]);
        $this->mock(CaseReportBuilder::class)->shouldReceive('build')->once()->andReturn($pdf);

        $this->get(route('client.cases.report', $case))->assertOk()->assertSee('Your report is ready');
        $download = DB::table('report_downloads')->first();
        $assembled = '';
        for ($part = 0; $part < $download->parts; $part++) {
            $response = $this->get(route('reports.download.part', [$download->id, $part]));
            $response->assertOk()->assertHeader('Cache-Control', 'no-store, private');
            $this->assertLessThanOrEqual(ReportDownload::PART_BYTES, strlen($response->getContent()));
            $assembled .= $response->getContent();
        }
        $this->assertSame($pdf, $assembled);
        $this->assertSame(hash('sha256', $pdf), $download->sha256);

        // Even another admin cannot use someone else's download identifier.
        $other = User::create([
            'name' => 'Other', 'email' => 'other@acme.test', 'password' => 'secret123',
            'role' => 'admin', 'is_verified' => true,
        ]);
        $this->actingAs($other)->get(route('reports.download.part', [$download->id, 0]))->assertNotFound();
    }

    public function test_expired_downloads_are_inaccessible_and_deleted(): void
    {
        $case = $this->makeCase();
        $this->actingAs($case->createdBy);
        $download = app(ReportDownload::class)->prepare($case, '%PDF-content', 'report.pdf');
        $this->travel(31)->minutes();
        $this->get(route('reports.download.part', [$download->id, 0]))->assertStatus(410);
        $this->assertDatabaseCount('report_downloads', 0);
        $this->assertDatabaseCount('report_download_parts', 0);
    }

    public function test_case_access_is_rechecked_for_each_part(): void
    {
        $case = $this->makeCase();
        $this->actingAs($case->createdBy);
        $download = app(ReportDownload::class)->prepare($case, '%PDF-content', 'report.pdf');
        $case->update(['client_organization_id' => ClientOrganization::create(['name' => 'Moved'])->id]);
        $this->get(route('reports.download.part', [$download->id, 0]))->assertForbidden();
    }

    public function test_small_report_is_still_a_direct_pdf_download(): void
    {
        $case = $this->makeCase();
        $this->actingAs($case->createdBy);
        config(['reports.chunked_downloads' => true]);
        $this->mock(CaseReportBuilder::class)->shouldReceive('build')->once()->andReturn('%PDF-small');
        $this->get(route('client.cases.report', $case))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseCount('report_downloads', 0);
    }
}
