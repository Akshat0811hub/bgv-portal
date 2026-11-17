<?php

namespace Tests\Feature;

use App\Models\Cases;
use App\Models\Check;
use App\Models\ClientOrganization;
use App\Models\User;
use Database\Seeders\CheckSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MultipleCheckDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): Cases
    {
        $this->seed(CheckSeeder::class);
        Mail::fake();
        Storage::fake('local');
        $organization = ClientOrganization::create(['name' => 'Multi-file organization']);
        $client = User::create([
            'name' => 'Client', 'email' => 'multi-client@example.test', 'password' => 'password123',
            'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $organization->id,
        ]);
        $case = Cases::create([
            'case_number' => Cases::generateCaseNumber(), 'candidate_name' => 'Candidate',
            'candidate_email' => 'candidate@example.test', 'created_by' => $client->id,
            'client_organization_id' => $organization->id, 'status' => 'Pending', 'case_start_date' => today(),
        ]);
        foreach (Check::orderBy('id')->pluck('slug') as $slug) {
            $case->checks()->create(['check_type' => $slug, 'status' => 'Pending']);
        }
        $case->uploadToken()->create(['token' => 'multiple-check-documents', 'expires_at' => now()->addDays(7)]);

        return $case;
    }

    private function files(string $prefix): array
    {
        return [
            UploadedFile::fake()->create($prefix.'-first.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create($prefix.'-second.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ];
    }

    public function test_every_seeded_check_renders_a_multiple_file_uploader(): void
    {
        $case = $this->makeCase();
        $response = $this->get(route('candidate.upload.show', $case->uploadToken->token))->assertOk();
        foreach (Check::all() as $check) {
            $schema = $check->schema ?? [];
            $fields = collect($schema)->flatMap(fn ($fields) => $fields)
                ->filter(fn ($field) => ($field['type'] ?? '') === 'file' || str_starts_with($field['name'] ?? '', '_file'));
            $names = $fields->isEmpty() ? ['_file'] : $fields->pluck('name')->all();
            foreach ($names as $name) {
                $response->assertSee('name="check_files['.$check->slug.']['.$name.'][]"', false);
            }
        }
        $response->assertSee('multiple accept=', false)->assertSee('Add files from a folder');
    }

    public function test_candidate_uploads_multiple_files_to_all_checks_and_both_employment_buckets(): void
    {
        $case = $this->makeCase();
        $uploads = [];
        foreach ($case->checks as $check) {
            $uploads[$check->check_type] = $check->check_type === 'employment_verification'
                ? ['_file_current' => $this->files('current'), '_file_previous' => $this->files('previous')]
                : ['_file' => $this->files($check->check_type)];
        }
        $this->post(route('candidate.upload.store', $case->uploadToken->token), [
            'check_files' => $uploads,
        ])->assertOk()->assertSee('Documents Submitted');

        foreach ($case->checks as $check) {
            $this->assertSame($check->check_type === 'employment_verification' ? 4 : 2, $check->documents()->count());
            $this->assertSame(0, $check->documents()->where('uploaded_by_role', '!=', 'candidate')->count());
            foreach ($check->documents as $document) {
                Storage::disk('local')->assertExists($document->stored_path);
            }
        }
        $employment = $case->checks->firstWhere('check_type', 'employment_verification');
        $this->assertSame(2, $employment->documents()->where('field_key', '_file_current')->count());
        $this->assertSame(2, $employment->documents()->where('field_key', '_file_previous')->count());
    }

    public function test_client_and_admin_additions_preserve_existing_files_for_employment_and_other_checks(): void
    {
        $case = $this->makeCase();
        $client = $case->createdBy;
        $admin = User::create([
            'name' => 'Admin', 'email' => 'multi-admin@example.test', 'password' => 'password123',
            'role' => 'admin', 'is_verified' => true,
        ]);
        foreach (['employment_verification', 'court_record_check'] as $slug) {
            $check = $case->checks->firstWhere('check_type', $slug);
            foreach (['batch1', 'batch2'] as $batch) {
                $this->actingAs($client)->post(route('client.cases.checks.documents', [$case, $check]), [
                    'files' => $this->files($batch),
                ])->assertRedirect()->assertSessionHasNoErrors();
                $this->actingAs($admin)->patch(route('admin.cases.checks.update', [$case, $check]), [
                    'status' => 'In Progress', 'verified_files' => $this->files($batch),
                ])->assertRedirect()->assertSessionHasNoErrors();
            }
            $this->assertSame(8, $check->documents()->count());
            $this->assertSame(4, $check->documents()->where('uploaded_by_role', 'client')->count());
            $this->assertSame(4, $check->documents()->where('uploaded_by_role', 'admin')->count());
        }
    }
}
