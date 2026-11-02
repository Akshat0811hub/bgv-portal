<?php

namespace Tests\Feature;

use App\Models\ClientOrganization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_requiring_reset_is_redirected_to_set_password(): void
    {
        $org  = ClientOrganization::create(['name' => 'Acme']);
        $user = User::create([
            'name' => 'New User', 'email' => 'new@acme.test', 'password' => 'temp12345',
            'role' => 'organization', 'is_verified' => true,
            'password_reset_required' => true, 'client_organization_id' => $org->id,
        ]);

        $this->actingAs($user)->get(route('client.dashboard'))
            ->assertRedirect(route('password.set'));

        // The set-password page itself is reachable.
        $this->actingAs($user)->get(route('password.set'))->assertOk();
    }

    public function test_setting_password_clears_the_flag_and_lands_on_dashboard(): void
    {
        $org  = ClientOrganization::create(['name' => 'Acme']);
        $user = User::create([
            'name' => 'New User', 'email' => 'new2@acme.test', 'password' => 'temp12345',
            'role' => 'organization', 'is_verified' => true,
            'password_reset_required' => true, 'client_organization_id' => $org->id,
        ]);

        $this->actingAs($user)->post(route('password.set.post'), [
            'password' => 'brandNew123',
            'password_confirmation' => 'brandNew123',
        ])->assertRedirect(route('client.dashboard'));

        $this->assertFalse((bool) $user->fresh()->password_reset_required);
    }
}
