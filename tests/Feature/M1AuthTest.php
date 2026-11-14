<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M1AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_register_login_and_logout_with_api_token(): void
    {
        $registered = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ])->assertCreated()->assertJsonPath('data.user.role', 'member');

        $token = $registered->json('data.token');
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertOk()->assertJsonPath('data.email', 'member@example.com');

        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$token])
            ->assertOk();

        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertUnauthorized();
    }

    public function test_inactive_member_cannot_sign_in_and_existing_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com', 'password' => 'password',
        ])->assertOk();

        $user->forceFill(['is_active' => false])->save();
        $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$login->json('data.token'),
        ])->assertUnauthorized();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com', 'password' => 'password',
        ])->assertUnprocessable();
    }

    public function test_admin_routes_reject_guests_and_member_accounts(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/');
        $member = User::factory()->create();
        $this->actingAs($member)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_can_view_members_and_change_status(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $member = User::factory()->create();

        $this->actingAs($admin)->get('/admin/members')->assertOk();
        $this->actingAs($admin)->patch('/admin/members/'.$member->id.'/status', [
            'is_active' => '0',
        ])->assertRedirect();
        $this->assertFalse($member->fresh()->is_active);
    }

    public function test_social_auth_returns_config_error_when_not_connected(): void
    {
        config()->set('social_auth.google_client_ids', []);
        $this->postJson('/api/v1/auth/google', [
            'identity_token' => 'not-configured',
        ])->assertStatus(503);
    }
}
