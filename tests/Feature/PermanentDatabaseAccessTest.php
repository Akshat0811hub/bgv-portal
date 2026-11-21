<?php

namespace Tests\Feature;

use App\Models\Cases;
use App\Models\ClientOrganization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermanentDatabaseAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unregistered_upload_link_cannot_create_cases(): void
    {
        $this->get(route('candidate.upload.show', 'unregistered-token'))->assertNotFound();
        $this->assertDatabaseCount('cases', 0);
        $this->assertDatabaseCount('upload_tokens', 0);
        $this->assertDatabaseCount('client_organizations', 0);
    }

    public function test_unsigned_snapshot_cannot_replace_an_existing_upload_token(): void
    {
        $org = ClientOrganization::create(['name' => 'Example Client']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password123', 'role' => 'admin', 'is_verified' => true]);
        $case = Cases::create(['case_number' => 'OV-SECURE', 'candidate_name' => 'Candidate', 'candidate_email' => 'candidate@example.test', 'client_organization_id' => $org->id, 'created_by' => $user->id, 'status' => 'Pending']);
        $original = $case->uploadToken()->create(['token' => 'genuine-token', 'used' => true, 'expires_at' => now()->subDay()]);
        $snapshot = base64_encode(json_encode(['cn' => $case->case_number, 'or' => $org->name]));
        $this->get(route('candidate.upload.show', 'forged-token').'?ctx='.urlencode($snapshot))->assertNotFound();
        $this->assertSame('genuine-token', $original->fresh()->token);
        $this->assertTrue($original->fresh()->used);
        $this->assertTrue($original->fresh()->expires_at->isPast());
        $this->assertDatabaseCount('cases', 1);
        $this->assertDatabaseCount('upload_tokens', 1);
    }

    public function test_failed_login_retains_email_without_flashing_password(): void
    {
        $this->post(route('login.post'), ['email' => 'missing@example.test', 'password' => 'private-password'])
            ->assertSessionHasErrors('email')
            ->assertSessionHas('_old_input.email', 'missing@example.test')
            ->assertSessionMissing('_old_input.password');
    }
}
