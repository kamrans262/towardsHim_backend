<?php

namespace Tests\Feature;

use App\Models\AudioItem;
use App\Models\User;
use App\Models\WeeklyChallenge;
use App\Services\MemberTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUploadFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();

        return $user;
    }

    private function days(): array
    {
        $days = [];
        for ($i = 1; $i <= 7; $i++) $days[$i] = 'Day '.$i.' — reflection and prayer.';

        return $days;
    }

    public function test_challenge_form_uses_existing_text_fields_and_only_image_audio_video_uploads(): void
    {
        $this->actingAs($this->admin())->get('/admin/challenges/create')
            ->assertOk()
            ->assertSee('Day 1')
            ->assertSee('Day 7')
            ->assertSee('id="challenge-day-1" data-day="1"  open', false)
            ->assertSee('id="challenge-day-7" data-day="7"', false)
            ->assertSee('day_media[1][upload][file]', false)
            ->assertSee('day_media[7][upload][type]', false)
            ->assertSee('Weekly Introduction Media (optional)')
            ->assertDontSee('Text / Caption')
            ->assertDontSee('Add Content')
            ->assertDontSee('media-add')
            ->assertDontSee('value="text"', false);
    }

    public function test_all_seven_daily_activities_are_collapsible_and_spacing_is_scoped_to_the_admin(): void
    {
        $this->actingAs($this->admin());
        $response = $this->get('/admin/challenges/create')->assertOk();
        $html = $response->getContent();

        $this->assertSame(7, substr_count($html, 'class="admin-day-panel"'));
        $this->assertSame(7, substr_count($html, 'class="admin-day-summary"'));
        $this->assertSame(7, substr_count($html, 'class="admin-day-body"'));
        $this->assertSame(7, substr_count($html, 'class="admin-day-instructions"'));
        $this->assertSame(7, substr_count($html, 'class="admin-simple-media admin-simple-media-compact"'));
        $this->assertSame(1, substr_count($html, 'id="challenge-day-1" data-day="1"  open'));
        $this->assertSame(7, substr_count($html, 'data-day-preview>'));
        $this->assertStringNotContainsString('Uploading the same type replaces the existing file', $html);
        $this->assertStringNotContainsString('Day 1 Media (optional)', $html);

        $css = file_get_contents(public_path('admin-assets/css/toward-admin.css'));
        $this->assertStringContainsString('.toward-admin .admin-day-panel[open]', $css);
        $this->assertStringContainsString('.toward-admin .admin-day-body{padding:', $css);
    }


    public function test_day_cards_render_with_rounded_style_and_right_hand_chevrons_even_without_asset_copy(): void
    {
        $this->actingAs($this->admin());
        $response = $this->get('/admin/challenges/create')->assertOk();
        $html = $response->getContent();

        $this->assertSame(7, substr_count($html, 'class="admin-day-chevron-wrap"'));
        $this->assertSame(7, substr_count($html, 'class="admin-day-chevron"'));
        $this->assertSame(7, substr_count($html, '<path d="m6 9 6 6 6-6"/>'));
        $this->assertStringContainsString('.admin-challenge-days .admin-day-panel {', $html);
        $this->assertStringContainsString('border-radius:16px!important', $html);
        $this->assertStringContainsString('gap:9px!important', $html);
        $this->assertStringContainsString('.admin-day-summary::-webkit-details-marker', $html);
        $this->assertStringContainsString('transform:rotate(180deg)!important', $html);
        $this->assertStringContainsString('day_media[1][upload][file]', $html);
        $this->assertStringContainsString('day_media[7][upload][file]', $html);
    }

    public function test_invalid_day_media_reports_its_specific_field(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $this->post('/admin/challenges', [
            'title' => 'Draft',
            'instructions' => 'Reflection',
            'action' => 'publish',
            'days' => [
                1 => 'Prayer',
                2 => 'Reflection',
                3 => 'Joy',
                4 => 'Hope',
                5 => 'Peace',
                6 => 'Gratitude',
                7 => 'Kindness',
            ],
            'day_media' => [
                5 => ['upload' => ['type' => 'video', 'file' => \Illuminate\Http\UploadedFile::fake()->image('wrong.png')]],
            ],
        ])->assertSessionHasErrors('day_media.5.upload.file');

        $this->assertDatabaseCount('weekly_challenges', 0);
    }

    public function test_saved_filenames_are_visible_with_removal_controls_in_listen_forms(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $this->post('/admin/audio/podcasts', [
            'title' => 'Multimedia podcast', 'description' => 'A recording and cover.',
            'status' => 'draft',
            'audio' => UploadedFile::fake()->create('prayer-recording.mp3', 15, 'audio/mpeg'),
            'video' => UploadedFile::fake()->create('reflection-film.mp4', 15, 'video/mp4'),
            'artwork' => UploadedFile::fake()->image('evening-cover.png'),
        ])->assertRedirect('/admin/audio/podcasts');

        $item = AudioItem::firstOrFail();
        $this->assertSame('prayer-recording.mp3', $item->audio_original_name);
        $this->assertSame('reflection-film.mp4', $item->video_original_name);
        $this->assertSame('evening-cover.png', $item->artwork_original_name);
        $this->get("/admin/audio/podcasts/{$item->id}/edit")
            ->assertOk()
            ->assertSee('prayer-recording.mp3')
            ->assertSee('reflection-film.mp4')
            ->assertSee('evening-cover.png')
            ->assertSee('name="remove_audio"', false)
            ->assertSee('name="remove_video"', false)
            ->assertSee('name="remove_artwork"', false)
            ->assertSee('data-upload-remove', false);

        $oldAudio = $item->audio_path;
        $this->patch("/admin/audio/podcasts/{$item->id}", [
            'title' => 'Multimedia podcast', 'description' => 'Still published.',
            'status' => 'draft',
            'audio' => UploadedFile::fake()->create('new-prayer.mp3', 10, 'audio/mpeg'),
            'remove_video' => 1,
            'remove_artwork' => 1,
        ])->assertRedirect('/admin/audio/podcasts');

        $item->refresh();
        $this->assertSame('new-prayer.mp3', $item->audio_original_name);
        Storage::disk('public')->assertMissing($oldAudio);
        $this->assertNull($item->video_path);
        $this->assertNull($item->artwork_path);
        $this->assertNull($item->video_original_name);
        $this->assertNull($item->artwork_original_name);

        $audioPath = $item->audio_path;
        $this->patch("/admin/audio/podcasts/{$item->id}", [
            'title' => 'Multimedia podcast', 'description' => 'No recordings remain.',
            'status' => 'draft', 'remove_audio' => 1,
        ])->assertRedirect('/admin/audio/podcasts');

        $this->assertNull($item->fresh()->audio_path);
        $this->assertNull($item->fresh()->audio_original_name);
        Storage::disk('public')->assertMissing($audioPath);
    }

    public function test_daily_upload_replaces_same_type_and_removing_it_does_not_clear_text_or_progress(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->post('/admin/challenges', [
            'title' => 'Prayer Week',
            'instructions' => 'Our weekly prayer journey.',
            'action' => 'publish', 'days' => $this->days(),
            'day_media' => [
                1 => [
                    'upload' => [
                        'type' => 'image', 'replace_type' => 1,
                        'file' => UploadedFile::fake()->image('day-one-prayer.png'),
                    ],
                ],
            ],
        ])->assertRedirect('/admin/challenges');

        $challenge = WeeklyChallenge::firstOrFail();
        $day = $challenge->days()->where('day_number', 1)->firstOrFail();
        $originalId = $day->id;
        $media = $day->media()->firstOrFail();
        $mediaId = $media->id;
        $originalPath = $media->file_path;
        $this->assertSame('day-one-prayer.png', $media->original_name);

        $member = User::factory()->create();
        $token = app(MemberTokenService::class)->issue($member)['token'];
        $headers = ['Authorization' => 'Bearer '.$token];
        $this->putJson("/api/v1/challenges/{$challenge->id}/days/1",
            ['completed' => true], $headers)->assertOk();

        $this->actingAs($admin);
        $this->get("/admin/challenges/{$challenge->id}/edit")
            ->assertOk()
            ->assertSee('day-one-prayer.png')
            ->assertSee("name=\"day_media[1][existing_{$mediaId}][remove]\"", false)
            ->assertSee('data-upload-remove', false);

        $days = $this->days();
        $days[1] = 'Updated day one instructions.';
        $this->patch("/admin/challenges/{$challenge->id}", [
            'title' => 'Prayer Week',
            'instructions' => 'Our weekly prayer journey.',
            'action' => 'publish', 'days' => $days,
            'day_media' => [
                1 => [
                    'upload' => [
                        'type' => 'image', 'replace_type' => 1,
                        'file' => UploadedFile::fake()->image('updated-day-one.png'),
                    ],
                ],
            ],
        ])->assertRedirect('/admin/challenges');

        $day->refresh();
        $this->assertSame($originalId, $day->id);
        $this->assertSame('Updated day one instructions.', $day->activity);
        $this->assertSame(1, $day->media()->where('type', 'image')->count());
        $replacement = $day->media()->firstOrFail();
        $this->assertSame($mediaId, $replacement->id);
        $this->assertSame('updated-day-one.png', $replacement->original_name);
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertExists($replacement->file_path);

        $this->patch("/admin/challenges/{$challenge->id}", [
            'title' => 'Prayer Week',
            'instructions' => 'Our weekly prayer journey.',
            'action' => 'publish', 'days' => $days,
            'day_media' => [
                1 => [
                    'existing_'.$replacement->id => [
                        'id' => $replacement->id,
                        'type' => 'image',
                        'remove' => 1,
                    ],
                ],
            ],
        ])->assertRedirect('/admin/challenges');

        $this->assertSame(0, $day->media()->count());
        Storage::disk('public')->assertMissing($replacement->file_path);
        $this->assertSame($originalId, $day->fresh()->id);
        $this->assertSame('Updated day one instructions.', $day->fresh()->activity);
        $this->getJson("/api/v1/challenges/{$challenge->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.days.0.completed', true)
            ->assertJsonPath('data.days.0.text', 'Updated day one instructions.');
    }

    public function test_older_stored_filename_still_displays_if_original_name_was_not_saved(): void
    {
        $item = AudioItem::create([
            'category' => 'prayer',
            'title' => 'Legacy Prayer',
            'description' => 'Old recording',
            'audio_path' => 'audio/abcdef123456.mp3',
            'is_published' => false,
        ]);
        $this->actingAs($this->admin())
            ->get("/admin/audio/prayers/{$item->id}/edit")
            ->assertOk()
            ->assertSee('abcdef123456.mp3')
            ->assertSee('name="remove_audio"', false);
    }
}
