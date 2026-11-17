<?php

namespace Tests\Feature;

use App\Mail\UserCredentialsMail;
use App\Models\ClientOrganization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'smtp']);
    }

    public function test_log_only_mode_warns_without_logging_credentials(): void
    {
        config(['mail.default' => 'log']);
        Mail::fake();
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Local Client', 'email' => 'local@acme.test', 'password' => 'temp12345',
            'role' => 'organization', 'client_org_name' => 'Acme Corp',
        ])->assertSessionHas('warning');
        $this->assertDatabaseHas('users', ['email' => 'local@acme.test']);
        Mail::assertNothingSent();
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@orbit.test', 'password' => 'secret123',
            'role' => 'admin', 'is_verified' => true,
        ]);
    }

    public function test_admin_creates_client_user_and_org_and_emails_credentials(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Jane Client', 'email' => 'jane@acme.test', 'password' => 'temp12345',
            'role' => 'organization', 'client_org_name' => 'Acme Corp',
        ])->assertRedirect(route('admin.users.index'))->assertSessionHas('success');

        $org = ClientOrganization::where('name', 'Acme Corp')->first();
        $this->assertNotNull($org, 'organization was not created');

        $user = User::where('email', 'jane@acme.test')->first();
        $this->assertNotNull($user, 'user was not created');
        $this->assertSame($org->id, $user->client_organization_id);
        $this->assertTrue((bool) $user->password_reset_required);

        Mail::assertSent(UserCredentialsMail::class, fn ($m) => $m->hasTo('jane@acme.test'));
    }

    public function test_user_is_still_created_when_email_fails(): void
    {
        // Simulate a mail transport failure (e.g. bad Brevo creds).
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Bob Client', 'email' => 'bob@acme.test', 'password' => 'temp12345',
            'role' => 'individual', 'client_org_name' => 'Beta Inc',
        ])->assertRedirect(route('admin.users.index'))->assertSessionHas('warning');

        // The account must exist despite the email failure.
        $this->assertDatabaseHas('users', ['email' => 'bob@acme.test']);
        $this->assertDatabaseHas('client_organizations', ['name' => 'Beta Inc']);
    }
}
