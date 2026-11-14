<?php

namespace Tests\Feature;

use App\Events\AudioPublished;
use App\Models\AudioItem;
use App\Models\AudioPublication;
use App\Models\MemberPushDevice;
use App\Models\PlatformContent;
use App\Models\User;
use App\Services\MemberTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M5ContentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        return $admin;
    }

    private function bearer(User $member): array
    {
        return ['Authorization' => 'Bearer '.app(MemberTokenService::class)->issue($member)['token']];
    }

    private function published(string $category = 'podcast'): AudioItem
    {
        return AudioItem::create([
            'category' => $category,
            'title' => 'A New Beginning',
            'description' => 'New episode',
            'audio_path' => 'audio/episode.mp3',
            'published_at' => now(),
            'is_published' => true,
        ]);
    }

    public function test_public_welcome_content_has_fallback_and_admin_can_edit_it(): void
    {
        $this->getJson('/api/v1/content')->assertOk()
            ->assertJsonPath('data.welcome_heading', PlatformContent::DEFAULTS['welcome_heading']);
        $this->get('/admin/content')->assertRedirect('/');
        $this->actingAs(User::factory()->create())->get('/admin/content')->assertForbidden();

        $this->actingAs($this->admin())
            ->patch('/admin/content', [
                'welcome_heading' => 'Welcome Home',
                'introduction_text' => 'Faith and encouragement for all.',
                'about_information' => 'Our journey begins here.',
            ])->assertRedirect();

        $this->getJson('/api/v1/content')->assertOk()
            ->assertJsonPath('data.welcome_heading', 'Welcome Home')
            ->assertJsonPath('data.introduction_text', 'Faith and encouragement for all.')
            ->assertJsonPath('data.about_information', 'Our journey begins here.');
        $this->assertDatabaseCount('platform_contents', 1);
    }

    public function test_first_publish_produces_one_notification_even_if_republished(): void
    {
        config()->set('services.firebase.push_enabled', false);
        $audio = $this->published('prayer');
        AudioPublished::dispatch($audio);
        AudioPublished::dispatch($audio);
        $this->assertDatabaseCount('audio_publications', 1);

        $member = User::factory()->create();
        $this->getJson('/api/v1/notifications', $this->bearer($member))
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content_id', (string) $audio->id)
            ->assertJsonPath('data.0.category', 'Prayer')
            ->assertJsonPath('data.0.is_read', false);
    }

    public function test_member_read_and_delete_state_are_independent_and_persisted(): void
    {
        $audio = $this->published();
        AudioPublished::dispatch($audio);
        $id = AudioPublication::firstOrFail()->id;
        $sarah = User::factory()->create();
        $alex = User::factory()->create();
        $sarahBearer = $this->bearer($sarah);
        $alexBearer = $this->bearer($alex);

        $this->patchJson("/api/v1/notifications/{$id}/read", [], $sarahBearer)->assertOk();

        $this->getJson('/api/v1/notifications', $sarahBearer)
            ->assertOk()->assertJsonPath('data.0.is_read', true);
        $this->getJson('/api/v1/notifications', $alexBearer)
            ->assertOk()->assertJsonPath('data.0.is_read', false);

        $this->deleteJson("/api/v1/notifications/{$id}", [], $sarahBearer)->assertOk();
        $this->getJson('/api/v1/notifications', $sarahBearer)->assertOk()
            ->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/notifications', $alexBearer)->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_draft_unpublished_and_deleted_audio_do_not_show_notifications(): void
    {
        $audio = $this->published();
        AudioPublished::dispatch($audio);
        $member = User::factory()->create();
        $headers = $this->bearer($member);

        $audio->update(['is_published' => false]);
        $this->getJson('/api/v1/notifications', $headers)->assertJsonCount(0, 'data');

        $audio->update(['is_published' => true]);
        $this->getJson('/api/v1/notifications', $headers)->assertJsonCount(1, 'data');
        $audio->delete();
        $this->getJson('/api/v1/notifications', $headers)->assertJsonCount(0, 'data');
    }

    public function test_only_member_can_register_and_revoke_their_push_device(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $token = str_repeat('F', 32);

        $this->postJson('/api/v1/devices/push', [
            'token' => $token, 'platform' => 'android',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/devices/push', [
            'token' => $token, 'platform' => 'android',
        ], $this->bearer($member))->assertOk();

        $this->assertDatabaseHas('member_push_devices', [
            'user_id' => $member->id, 'token' => $token,
        ]);

        $this->deleteJson('/api/v1/devices/push', [
            'token' => $token,
        ], $this->bearer($other))->assertOk();

        $this->assertDatabaseCount('member_push_devices', 1);

        $this->deleteJson('/api/v1/devices/push', [
            'token' => $token,
        ], $this->bearer($member))->assertOk();

        $this->assertDatabaseCount('member_push_devices', 0);
    }
}
