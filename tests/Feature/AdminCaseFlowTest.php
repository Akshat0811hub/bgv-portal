<?php

namespace Tests\Feature;

use App\Mail\CandidateInviteMail;
use App\Models\Cases;
use App\Models\ClientOrganization;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCaseFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ClientOrganization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CheckSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@orbit.test', 'password' => 'secret123',
            'role' => 'admin', 'is_verified' => true,
        ]);
        $this->org = ClientOrganization::create(['name' => 'Acme Corp']);
    }

    public function test_admin_can_view_create_case_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.cases.create'))
            ->assertOk()
            ->assertSee('Create New Case')
            ->assertSee('Acme Corp');
    }

    public function test_admin_upload_mode_creates_case_and_captures_data(): void
    {
        Mail::fake();
        Storage::fake('local');

        $response = $this->actingAs($this->admin)->post(route('admin.cases.store'), [
            'client_organization_id' => $this->org->id,
            'candidate_name'  => 'John Doe',
            'candidate_email' => 'john@example.test',
            'mode'            => 'admin_upload',
            'checks'          => ['national_id_verification'],
            'check_data'      => [
                'national_id_verification' => ['_self' => ['pan' => 'ABCDE1234F']],
            ],
            'check_files'     => [
                'national_id_verification' => ['_file' => [UploadedFile::fake()->create('id.pdf', 50, 'application/pdf')]],
            ],
        ]);

        $case = Cases::first();
        $response->assertRedirect(route('admin.cases.show', $case));

        $check = $case->checks->firstWhere('check_type', 'national_id_verification');
        $this->assertSame('ABCDE1234F', $check->form_data['_self']['pan']);
        $this->assertSame('In Progress', $check->fresh()->status);

        $doc = Document::where('case_check_id', $check->id)->first();
        $this->assertNotNull($doc);
        $this->assertSame('admin', $doc->uploaded_by_role);
        $this->assertSame($this->admin->id, $doc->uploaded_by);
        Mail::assertSent(CandidateInviteMail::class, fn ($mail) =>
            $mail->hasTo('john@example.test') && ! $mail->requestDocuments
        );
    }

    public function test_send_link_mode_emails_candidate(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)->post(route('admin.cases.store'), [
            'client_organization_id' => $this->org->id,
            'candidate_name'  => 'Link Person',
            'candidate_email' => 'link@example.test',
            'mode'            => 'send_link',
            'checks'          => ['education_verification'],
        ]);

        $case = Cases::first();
        $this->assertNotNull($case->uploadToken);
        Mail::assertSent(CandidateInviteMail::class, fn ($m) => $m->hasTo('link@example.test') && $m->requestDocuments);
    }

    public function test_client_creation_notifies_candidate_in_every_mode(): void
    {
        $client = User::create([
            'name' => 'Client', 'email' => 'client@orbit.test', 'password' => 'secret123',
            'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $this->org->id,
        ]);

        foreach (['send_link', 'self_upload', 'admin_upload'] as $mode) {
            Mail::fake();
            $this->actingAs($client)->post(route('client.cases.store'), [
                'candidate_name' => 'Candidate', 'candidate_email' => 'candidate@example.test',
                'mode' => $mode, 'checks' => ['education_verification'],
            ])->assertRedirect();
            Mail::assertSent(CandidateInviteMail::class, fn ($mail) =>
                $mail->hasTo('candidate@example.test') && $mail->requestDocuments === ($mode === 'send_link')
            );
            Mail::assertSentCount(1);
        }
    }

    public function test_admin_can_download_pdf_report(): void
    {
        $case = Cases::create([
            'case_number' => Cases::generateCaseNumber(),
            'client_organization_id' => $this->org->id,
            'created_by' => $this->admin->id,
            'status' => 'Pending', 'candidate_name' => 'Pdf Person',
            'candidate_email' => 'pdf@example.test', 'case_start_date' => now()->toDateString(),
        ]);
        $case->checks()->create(['check_type' => 'education_verification', 'status' => 'Clear']);

        $response = $this->actingAs($this->admin)->get(route('admin.cases.report', $case));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_resend_link_issues_token_and_emails(): void
    {
        Mail::fake();
        $case = Cases::create([
            'case_number' => Cases::generateCaseNumber(),
            'client_organization_id' => $this->org->id,
            'created_by' => $this->admin->id,
            'status' => 'Pending', 'candidate_name' => 'Resend Person',
            'candidate_email' => 'resend@example.test', 'case_start_date' => now()->toDateString(),
        ]);
        $case->checks()->create(['check_type' => 'education_verification', 'status' => 'Pending']);

        $this->actingAs($this->admin)->post(route('admin.cases.resend-link', $case))->assertRedirect();

        $this->assertNotNull($case->fresh()->uploadToken);
        Mail::assertSent(CandidateInviteMail::class);
    }
}
