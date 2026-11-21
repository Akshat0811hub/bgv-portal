<?php

namespace Tests\Feature;

use App\Models\Cases;
use App\Models\ClientOrganization;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class SensitiveResponseCachingTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): Cases
    {
        $organization = ClientOrganization::create(['name' => 'Cache Test Client']);
        $client = User::create([
            'name' => 'Client', 'email' => 'cache-client@example.test', 'password' => 'test-password',
            'role' => 'organization', 'is_verified' => true, 'client_organization_id' => $organization->id,
        ]);

        return Cases::create([
            'case_number' => 'OV-CACHE-TEST', 'candidate_name' => 'Candidate',
            'candidate_email' => 'cache-candidate@example.test', 'created_by' => $client->id,
            'client_organization_id' => $organization->id, 'status' => 'Pending',
        ]);
    }

    private function assertNotStored(TestResponse $response): void
    {
        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache');
    }

    public function test_authenticated_admin_and_client_html_cannot_be_cached(): void
    {
        $case = $this->makeCase();
        $admin = User::create([
            'name' => 'Admin', 'email' => 'cache-admin@example.test', 'password' => 'test-password',
            'role' => 'admin', 'is_verified' => true,
        ]);

        $this->assertNotStored($this->actingAs($admin)->get(route('admin.dashboard')));
        $this->assertNotStored($this->actingAs($case->createdBy)->get(route('client.dashboard')));
    }

    public function test_candidate_token_page_cannot_be_cached(): void
    {
        $case = $this->makeCase();
        $token = $case->uploadToken()->create(['token' => 'cache-test-candidate-token', 'expires_at' => now()->addDay()]);

        $this->assertNotStored($this->get(route('candidate.upload.show', $token->token)));
    }

    public function test_disk_original_overrides_binary_file_response_public_cache_default(): void
    {
        Storage::fake('local');
        $case = $this->makeCase();
        $bytes = '%PDF-1.4 cache test original';
        $path = "cases/{$case->id}/original.pdf";
        Storage::disk('local')->put($path, $bytes);
        $document = Document::create([
            'case_id' => $case->id, 'original_name' => 'original.pdf', 'stored_path' => $path,
            'mime_type' => 'application/pdf', 'file_size' => strlen($bytes), 'uploaded_by_role' => 'candidate',
        ]);

        $response = $this->actingAs($case->createdBy)->get(route('documents.view', $document));

        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertNotStored($response);
    }

    public function test_database_original_fallback_cannot_be_cached(): void
    {
        Storage::fake('local');
        $case = $this->makeCase();
        $bytes = '%PDF-1.4 database original';
        $document = Document::create([
            'case_id' => $case->id, 'original_name' => 'fallback.pdf', 'stored_path' => "cases/{$case->id}/missing.pdf",
            'mime_type' => 'application/pdf', 'file_size' => strlen($bytes),
            'file_content' => base64_encode($bytes), 'uploaded_by_role' => 'candidate',
        ]);

        $response = $this->actingAs($case->createdBy)->get(route('documents.view', $document));

        $this->assertNotStored($response);
        $response->assertContent($bytes);
    }
}
