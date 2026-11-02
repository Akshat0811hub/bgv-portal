<?php

namespace Tests\Feature;

use App\Models\Cases;
use App\Models\ClientOrganization;
use App\Models\Document;
use App\Models\UploadToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CheckSeeder::class);
    }

    private function makeCase(array $checkSlugs): Cases
    {
        $org  = ClientOrganization::create(['name' => 'Acme Corp']);
        $user = User::create([
            'name' => 'HR User', 'email' => 'hr@acme.test', 'password' => 'secret123',
            'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $org->id,
        ]);

        $case = Cases::create([
            'case_number' => Cases::generateCaseNumber(),
            'client_organization_id' => $org->id,
            'created_by' => $user->id,
            'status' => 'Pending',
            'candidate_name' => 'Jane Candidate',
            'candidate_email' => 'jane@example.test',
            'case_start_date' => now()->toDateString(),
        ]);

        foreach ($checkSlugs as $slug) {
            $case->checks()->create(['check_type' => $slug, 'status' => 'Pending']);
        }

        $case->uploadToken()->create(['token' => 'tok_'.str()->random(20), 'expires_at' => now()->addDays(7)]);

        return $case->fresh('checks', 'uploadToken');
    }

    public function test_candidate_can_view_upload_page(): void
    {
        $case = $this->makeCase(['national_id_verification']);

        $this->get(route('candidate.upload.show', $case->uploadToken->token))
            ->assertOk()
            ->assertSee('Aadhaar Number')      // schema field rendered
            ->assertSee('Jane Candidate');
    }

    public function test_candidate_submission_captures_structured_data_and_files(): void
    {
        Storage::fake('local');
        $case = $this->makeCase(['national_id_verification', 'address_verification']);

        $response = $this->post(route('candidate.upload.store', $case->uploadToken->token), [
            'check_data' => [
                'national_id_verification' => [
                    '_self' => ['aadhaar' => '1234 5678 9012', 'pan' => 'ABCDE1234F'],
                ],
                'address_verification' => [
                    'Current Address' => ['line1' => '12 MG Road', 'city' => 'Bengaluru', 'pincode' => '560001'],
                ],
            ],
            'check_files' => [
                'national_id_verification' => [
                    '_file' => [UploadedFile::fake()->create('aadhaar.pdf', 100, 'application/pdf')],
                ],
            ],
        ]);

        $response->assertOk()->assertSee('Documents Submitted');

        $nid = $case->checks->firstWhere('check_type', 'national_id_verification')->fresh();
        $this->assertSame('1234 5678 9012', $nid->form_data['_self']['aadhaar']);
        $this->assertSame('ABCDE1234F', $nid->form_data['_self']['pan']);
        $this->assertSame('In Progress', $nid->status);

        $addr = $case->checks->firstWhere('check_type', 'address_verification')->fresh();
        $this->assertSame('Bengaluru', $addr->form_data['Current Address']['city']);

        // File stored + Document record created against the right check.
        $this->assertSame(1, Document::where('case_check_id', $nid->id)->count());
        $doc = Document::where('case_check_id', $nid->id)->first();
        Storage::disk('local')->assertExists($doc->stored_path);
        $this->assertSame('candidate', $doc->uploaded_by_role);

        // Case status recalculated, token consumed.
        $this->assertSame('In Progress', $case->fresh()->status);
        $this->assertTrue((bool) $case->uploadToken->fresh()->used);
    }
}
