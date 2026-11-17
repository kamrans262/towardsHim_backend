<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
        $code = null;
        Mail::shouldReceive('raw')->once()->andReturnUsing(function ($message) use (&$code): void {
            preg_match('/code is: (\d{6})/', $message, $matches);
            $code = $matches[1] ?? null;
        });
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com', 'password' => 'password',
        ])->assertOk()->assertJsonMissingPath('data.token');

        $this->assertNotNull($code);
        $verified = $this->postJson('/api/v1/auth/login/verify', [
            'challenge' => $login->json('data.challenge'),
            'code' => $code,
        ])->assertOk();
        $this->assertNotNull($verified->json('data.token'));

        $user->forceFill(['is_active' => false])->save();
        $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$verified->json('data.token'),
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

        $code = null;
        Mail::shouldReceive('raw')->once()->andReturnUsing(function ($message) use (&$code): void {
            preg_match('/code is: (\d{6})/', $message, $matches);
            $code = $matches[1] ?? null;
        });
        $pending = $this->postJson('/api/v1/auth/login', [
            'email' => 'flutterdb0@gmail.com',
            'password' => 'StrongPassword123',
        ])->assertOk()->assertJsonMissingPath('data.token');

        $mobileLogin = $this->postJson('/api/v1/auth/login/verify', [
            'challenge' => $pending->json('data.challenge'),
            'code' => $code,
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

    public function test_wrong_password_never_sends_otp_or_creates_a_token(): void
    {
        Mail::shouldNotReceive('raw');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.com', 'password' => 'invalid',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('login_otp_challenges', 0);
        $this->assertDatabaseCount('member_access_tokens', 0);
    }

    public function test_five_bad_codes_are_persisted_and_expired_codes_cannot_login(): void
    {
        $member = User::factory()->create(['email' => 'member@example.com']);
        $code = null;
        Mail::shouldReceive('raw')->once()->andReturnUsing(function ($message) use (&$code): void {
            preg_match('/code is: (\d{6})/', $message, $matches);
            $code = $matches[1] ?? null;
        });
        $pending = $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com', 'password' => 'password',
        ])->assertOk();

        $wrong = $code === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login/verify', [
                'challenge' => $pending->json('data.challenge'), 'code' => $wrong,
            ])->assertUnprocessable();
        }
        $this->assertDatabaseHas('login_otp_challenges', [
            'user_id' => $member->id, 'attempts' => 5,
        ]);
        $this->postJson('/api/v1/auth/login/verify', [
            'challenge' => $pending->json('data.challenge'), 'code' => $code,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('member_access_tokens', 0);
    }

    public function test_expired_login_otp_cannot_be_reused(): void
    {
        User::factory()->create(['email' => 'member@example.com']);
        $code = null;
        Mail::shouldReceive('raw')->once()->andReturnUsing(function ($message) use (&$code): void {
            preg_match('/code is: (\d{6})/', $message, $matches);
            $code = $matches[1] ?? null;
        });

        $pending = $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com', 'password' => 'password',
        ])->assertOk();

        DB::table('login_otp_challenges')->update(['expires_at' => now()->subSecond()]);
        $this->postJson('/api/v1/auth/login/verify', [
            'challenge' => $pending->json('data.challenge'), 'code' => $code,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('member_access_tokens', 0);
    }

    public function test_resend_replaces_old_code_and_requires_cooldown(): void
    {
        User::factory()->create(['email' => 'member@example.com']);
        $codes = [];
        Mail::shouldReceive('raw')->twice()->andReturnUsing(function ($message) use (&$codes): void {
            preg_match('/code is: (\d{6})/', $message, $matches);
            $codes[] = $matches[1] ?? null;
        });

        $pending = $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com', 'password' => 'password',
        ])->assertOk();
        $challenge = $pending->json('data.challenge');

        $this->postJson('/api/v1/auth/login/resend', [
            'challenge' => $challenge,
        ])->assertUnprocessable();

        $this->travel(61)->seconds();
        $this->postJson('/api/v1/auth/login/resend', [
            'challenge' => $challenge,
        ])->assertOk()->assertJsonPath('data.resend_after_seconds', 60);
        $this->assertCount(2, $codes);

        $this->postJson('/api/v1/auth/login/verify', [
            'challenge' => $challenge, 'code' => $codes[1],
        ])->assertOk()->assertJsonPath('data.user.email', 'member@example.com');
        $this->postJson('/api/v1/auth/login/verify', [
            'challenge' => $challenge, 'code' => $codes[1],
        ])->assertUnprocessable();
    }
}
