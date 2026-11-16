<?php

namespace Tests\Feature;

use App\Events\AudioPublished;
use App\Models\AudioItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class M2AudioTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        return $admin;
    }

    public function test_guests_only_receive_published_audio_and_can_filter_categories(): void
    {
        AudioItem::create([
            'category' => 'podcast', 'title' => 'Gratitude', 'description' => 'A podcast',
            'audio_path' => 'audio/gratitude.mp3', 'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        AudioItem::create([
            'category' => 'prayer', 'title' => 'Private draft', 'description' => 'Not published',
            'is_published' => false,
        ]);
        $this->getJson('/api/v1/audio?category=podcast')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Gratitude')
            ->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/audio?category=prayer')->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_only_administrators_can_manage_audio(): void
    {
        $this->get('/admin/audio/podcasts')->assertRedirect('/');
        $this->actingAs(User::factory()->create())
            ->get('/admin/audio/podcasts')->assertForbidden();
        $this->actingAs($this->admin())
            ->get('/admin/audio/podcasts')->assertOk();
    }

    public function test_first_publish_dispatches_event_but_edit_does_not(): void
    {
        Event::fake([AudioPublished::class]);
        Storage::fake('public');
        $this->actingAs($this->admin());
        $response = $this->post('/admin/audio/podcasts', [
            'title' => 'A new beginning', 'description' => 'Weekly podcast episode',
            'duration_minutes' => 24, 'status' => 'published',
            'audio' => UploadedFile::fake()->create('episode.mp3', 100, 'audio/mpeg'),
        ]);
        $response->assertRedirect('/admin/audio/podcasts');
        $audio = AudioItem::firstOrFail();
        $this->assertTrue($audio->is_published);
        Event::assertDispatchedTimes(AudioPublished::class, 1);

        $this->patch('/admin/audio/podcasts/'.$audio->id, [
            'title' => 'A new beginning edited', 'description' => 'Updated description',
            'duration_minutes' => 24, 'status' => 'published',
        ])->assertRedirect('/admin/audio/podcasts');
        Event::assertDispatchedTimes(AudioPublished::class, 1);
    }

    public function test_text_only_content_can_publish_and_published_detail_is_public(): void
    {
        $this->actingAs($this->admin())->post('/admin/audio/prayers', [
            'title' => 'Morning Prayer', 'description' => 'Pray with us',
            'status' => 'published',
        ])->assertRedirect('/admin/audio/prayers');
        $this->getJson('/api/v1/audio?category=prayer')->assertOk()
            ->assertJsonPath('data.0.title', 'Morning Prayer')
            ->assertJsonPath('data.0.audio_url', null);

        $audio = AudioItem::create([
            'category' => 'meditation', 'title' => 'Peace', 'description' => 'Breathe',
            'audio_path' => 'audio/peace.mp3', 'is_published' => true, 'published_at' => now(),
        ]);
        $this->getJson('/api/v1/audio/'.$audio->id)->assertOk()
            ->assertJsonPath('data.category', 'meditation');

        $audio->update(['is_published' => false]);
        $this->getJson('/api/v1/audio/'.$audio->id)->assertNotFound();
    }
}
