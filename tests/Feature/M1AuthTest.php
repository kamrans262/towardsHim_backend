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

    public function test_admin_account_can_sign_in_to_both_web_and_mobile_using_one_email(): void
    {
        $admin = User::factory()->create([
            'email' => 'flutterdb0@gmail.com',
            'password' => 'StrongPassword123',
        ]);
        $admin->forceFill(['role' => 'admin'])->save();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isMember());

        $this->post('/', [
            'email' => 'flutterdb0@gmail.com',
            'password' => 'StrongPassword123',
        ])->assertRedirect('/admin/dashboard');

        $this->get('/admin/dashboard')->assertOk();

        $mobileLogin = $this->postJson('/api/v1/auth/login', [
            'email' => 'flutterdb0@gmail.com',
            'password' => 'StrongPassword123',
        ])->assertOk()->assertJsonPath('data.user.role', 'admin');

        $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$mobileLogin->json('data.token'),
        ])->assertOk()->assertJsonPath('data.email', 'flutterdb0@gmail.com');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_member_can_explicitly_gain_admin_access_without_password_change(): void
    {
        $user = User::factory()->create([
            'email' => 'flutterdb0@gmail.com',
            'password' => 'StrongPassword123',
        ]);
        $originalHash = $user->password;

        $this->artisan('toward:admin-create', [
            'email' => 'flutterdb0@gmail.com',
            '--grant-existing' => true,
        ])->expectsConfirmation(
            'Grant administrator access to flutterdb0@gmail.com? The existing password and mobile app access will remain unchanged.',
            'yes'
        )->assertSuccessful();

        $user->refresh();
        $this->assertSame($originalHash, $user->password);
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->isMember());
        $this->assertDatabaseCount('users', 1);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'flutterdb0@gmail.com',
            'password' => 'StrongPassword123',
        ])->assertOk();

        // Repeated invocation is safe and does not change credentials.
        $this->artisan('toward:admin-create', [
            'email' => 'flutterdb0@gmail.com',
            '--grant-existing' => true,
        ])->assertSuccessful();
        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_admin_grant_requires_explicit_flag(): void
    {
        $user = User::factory()->create(['email' => 'flutterdb0@gmail.com']);

        $this->artisan('toward:admin-create', [
            'email' => 'flutterdb0@gmail.com',
        ])->assertFailed();

        $this->assertFalse($user->fresh()->isAdmin());
    }
}
