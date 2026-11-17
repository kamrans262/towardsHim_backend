<?php

namespace Tests\Feature;

use App\Models\AudioItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListenVideoUploadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    public function test_listen_forms_have_a_video_upload_without_the_extra_media_editor(): void
    {
        $this->actingAs($this->admin());

        foreach (['podcasts', 'meditations', 'prayers'] as $category) {
            $this->get("/admin/audio/{$category}/create")
                ->assertOk()
                ->assertSee('Video File (optional)')
                ->assertSee('name="video"', false)
                ->assertDontSee('Extra Audio, Video, Images &amp; Text')
                ->assertDontSee('media-add');
        }

        $this->get('/admin/challenges/create')
            ->assertOk()
            ->assertSee('Weekly Introduction Media')
            ->assertSee('media-add');
    }

    public function test_published_audio_and_video_have_separate_urls_and_support_replacement(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->post('/admin/audio/podcasts', [
            'title' => 'Video podcast',
            'description' => 'An episode with an audio recording and a video.',
            'status' => 'published',
            'audio' => UploadedFile::fake()->create('episode.mp3', 24, 'audio/mpeg'),
            'video' => UploadedFile::fake()->create('episode.mp4', 55, 'video/mp4'),
        ])->assertRedirect('/admin/audio/podcasts');

        $item = AudioItem::firstOrFail();
        $originalVideo = $item->video_path;
        $originalAudio = $item->audio_path;
        $this->assertNotNull($originalVideo);
        Storage::disk('public')->assertExists($originalVideo);
        Storage::disk('public')->assertExists($originalAudio);

        $this->getJson("/api/v1/audio/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.video_url', $item->video_url)
            ->assertJsonPath('data.audio_url', $item->audio_url);
        $this->getJson('/api/v1/audio?category=podcast')
            ->assertOk()
            ->assertJsonPath('data.0.video_url', $item->video_url);

        $item->media()->create([
            'type' => 'text',
            'body' => 'Old media from previous editor must survive.',
            'position' => 0,
        ]);

        // Edits without a new video preserve uploaded and legacy media.
        $this->patch("/admin/audio/podcasts/{$item->id}", [
            'title' => 'Video podcast updated',
            'description' => 'Updated text',
            'status' => 'published',
        ])->assertRedirect('/admin/audio/podcasts');
        $this->assertSame($originalVideo, $item->fresh()->video_path);
        $this->assertSame(1, $item->media()->count());

        $this->patch("/admin/audio/podcasts/{$item->id}", [
            'title' => 'Video podcast updated',
            'description' => 'Updated text',
            'status' => 'published',
            'video' => UploadedFile::fake()->create('replacement.webm', 55, 'video/webm'),
        ])->assertRedirect('/admin/audio/podcasts');
        $replacement = $item->fresh()->video_path;
        $this->assertNotSame($originalVideo, $replacement);
        Storage::disk('public')->assertMissing($originalVideo);
        Storage::disk('public')->assertExists($replacement);
        Storage::disk('public')->assertExists($originalAudio);

        $this->patch("/admin/audio/podcasts/{$item->id}", [
            'title' => 'Video podcast updated',
            'description' => 'Updated text',
            'status' => 'published',
            'remove_video' => '1',
        ])->assertRedirect('/admin/audio/podcasts');
        $this->assertNull($item->fresh()->video_path);
        Storage::disk('public')->assertMissing($replacement);
        Storage::disk('public')->assertExists($originalAudio);

        $this->getJson("/api/v1/audio/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.video_url', null)
            ->assertJsonPath('data.media.0.text', 'Old media from previous editor must survive.');
    }

    public function test_rejects_an_image_uploaded_as_video(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->post('/admin/audio/prayers', [
            'title' => 'Prayer',
            'description' => 'Prayer description',
            'status' => 'draft',
            'video' => UploadedFile::fake()->image('not-video.png'),
        ])->assertSessionHasErrors('video');

        $this->assertDatabaseCount('audio_items', 0);
    }

    public function test_deleting_an_item_removes_the_uploaded_video(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $this->post('/admin/audio/meditations', [
            'title' => 'Video meditation',
            'description' => 'Meditation description',
            'status' => 'draft',
            'video' => UploadedFile::fake()->create('meditation.mp4', 44, 'video/mp4'),
        ])->assertRedirect();

        $item = AudioItem::firstOrFail();
        $path = $item->video_path;
        Storage::disk('public')->assertExists($path);

        $this->delete("/admin/audio/meditations/{$item->id}")->assertRedirect();
        $this->assertDatabaseCount('audio_items', 0);
        Storage::disk('public')->assertMissing($path);
    }
}
