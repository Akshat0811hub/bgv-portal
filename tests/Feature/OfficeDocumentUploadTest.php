<?php

namespace Tests\Feature;

use App\Models\Cases;
use App\Models\ClientOrganization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficeDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): Cases
    {
        $this->seed(\Database\Seeders\CheckSeeder::class);
        Storage::fake('local');
        Mail::fake();
        $org = ClientOrganization::create(['name' => 'Acme']);
        $client = User::create([
            'name' => 'HR', 'email' => 'hr@acme.test', 'password' => 'secret123',
            'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $org->id,
        ]);
        $case = Cases::create([
            'case_number' => Cases::generateCaseNumber(), 'candidate_name' => 'Candidate',
            'candidate_email' => 'candidate@example.test', 'created_by' => $client->id,
            'client_organization_id' => $org->id, 'status' => 'Pending', 'case_start_date' => today(),
        ]);
        $case->checks()->create(['check_type' => 'employment_verification', 'status' => 'Pending']);
        $case->uploadToken()->create(['token' => 'office-upload-token', 'expires_at' => now()->addDays(7)]);

        return $case;
    }

    private function officeFiles(): array
    {
        return [
            UploadedFile::fake()->create('employment.doc', 10, 'application/msword'),
            UploadedFile::fake()->create('letter.odt', 10, 'application/vnd.oasis.opendocument.text'),
            UploadedFile::fake()->create('reference.rtf', 10, 'application/rtf'),
        ];
    }

    public function test_candidate_can_submit_multiple_office_documents_under_one_check(): void
    {
        $case = $this->makeCase();
        $this->post(route('candidate.upload.store', $case->uploadToken->token), [
            'check_files' => ['employment_verification' => ['_file' => $this->officeFiles()]],
        ])->assertOk()->assertSee('Documents Submitted');
        $this->assertSame(3, $case->documents()->where('uploaded_by_role', 'candidate')->count());
    }

    public function test_client_can_add_multiple_office_documents_to_an_existing_check(): void
    {
        $case = $this->makeCase();
        $this->actingAs($case->createdBy)->post(route('client.cases.checks.documents', [$case, $case->checks->first()]), [
            'files' => $this->officeFiles(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(3, $case->documents()->where('uploaded_by_role', 'client')->count());
    }

    public function test_admin_can_upload_multiple_verified_office_files(): void
    {
        $case = $this->makeCase();
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@acme.test', 'password' => 'secret123',
            'role' => 'admin', 'is_verified' => true,
        ]);
        $this->actingAs($admin)->patch(route('admin.cases.checks.update', [$case, $case->checks->first()]), [
            'status' => 'In Progress', 'verified_files' => $this->officeFiles(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(3, $case->documents()->where('uploaded_by_role', 'admin')->count());
    }
}
