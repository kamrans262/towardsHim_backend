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

class MultimediaContentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        return $admin;
    }

    private function days(): array
    {
        $days = [];
        for ($i = 1; $i <= 7; $i++) $days[$i] = "Day $i introduction";
        return $days;
    }

    public function test_listen_can_publish_text_image_video_and_audio_together(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin())->post('/admin/audio/podcasts', [
            'title' => 'Multimedia Podcast',
            'description' => 'Introduction and supporting materials',
            'status' => 'published',
            'media' => [
                'new1' => ['type' => 'text', 'body' => 'Read this reflection.'],
                'new2' => [
                    'type' => 'image',
                    'body' => 'Illustration',
                    'file' => UploadedFile::fake()->image('reflection.png'),
                ],
                'new3' => [
                    'type' => 'audio',
                    'body' => 'Original recording',
                    'file' => UploadedFile::fake()->create('clip.mp3', 50, 'audio/mpeg'),
                ],
                'new4' => [
                    'type' => 'video',
                    'body' => 'Watch the introduction',
                    'file' => UploadedFile::fake()->create('clip.mp4', 50, 'video/mp4'),
                ],
            ],
        ])->assertRedirect('/admin/audio/podcasts');

        $item = AudioItem::firstOrFail();
        $this->assertTrue($item->is_published);
        $this->assertSame(4, $item->media()->count());
        $this->getJson("/api/v1/audio/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.media.0.type', 'text')
            ->assertJsonPath('data.media.0.text', 'Read this reflection.')
            ->assertJsonPath('data.media.1.type', 'image')
            ->assertJsonPath('data.media.2.type', 'audio')
            ->assertJsonPath('data.media.3.type', 'video');
    }

    public function test_weekly_and_daily_multimedia_preserves_completed_day_ids(): void
    {
        Storage::fake('public');
        $member = User::factory()->create();
        $headers = ['Authorization' => 'Bearer '.app(MemberTokenService::class)->issue($member)['token']];

        $this->actingAs($this->admin())->post('/admin/challenges', [
            'title' => 'Seven Day Multimedia Journey',
            'instructions' => '',
            'action' => 'publish',
            'days' => $this->days(),
            'media' => [
                'new1' => ['type' => 'text', 'body' => 'Weekly introduction'],
            ],
            'day_media' => [
                '2' => [
                    'new2' => [
                        'type' => 'image',
                        'file' => UploadedFile::fake()->image('day2.png'),
                    ],
                ],
                '3' => [
                    'new3' => [
                        'type' => 'video',
                        'file' => UploadedFile::fake()->create('day3.mp4', 50, 'video/mp4'),
                    ],
                ],
            ],
        ])->assertRedirect('/admin/challenges');

        $challenge = WeeklyChallenge::firstOrFail();
        $day = $challenge->days()->where('day_number', 2)->firstOrFail();
        $dayId = $day->id;
        $this->getJson("/api/v1/challenges/{$challenge->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.media.0.text', 'Weekly introduction')
            ->assertJsonPath('data.days.1.media.0.type', 'image')
            ->assertJsonPath('data.days.2.media.0.type', 'video');

        $this->putJson("/api/v1/challenges/{$challenge->id}/days/2", [
            'completed' => true,
        ], $headers)->assertOk();

        $updated = $this->days();
        $updated[2] = 'Updated activity text';
        $this->actingAs($this->admin())->patch("/admin/challenges/{$challenge->id}", [
            'title' => 'Updated Challenge',
            'instructions' => 'Edited introduction',
            'action' => 'publish',
            'days' => $updated,
        ])->assertRedirect('/admin/challenges');

        $this->assertSame($dayId, $challenge->days()->where('day_number', 2)->firstOrFail()->id);
        $this->getJson("/api/v1/challenges/{$challenge->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.days.1.completed', true)
            ->assertJsonPath('data.days.1.media.0.type', 'image');
    }

    public function test_media_cannot_be_edited_from_another_item(): void
    {
        $admin = $this->admin();
        $first = AudioItem::create([
            'category' => 'podcast', 'title' => 'First',
            'description' => 'First item', 'is_published' => false,
        ]);
        $other = AudioItem::create([
            'category' => 'podcast', 'title' => 'Second',
            'description' => 'Second item', 'is_published' => false,
        ]);
        $block = $other->media()->create(['type' => 'text', 'body' => 'Private']);
        $this->actingAs($admin)->patch("/admin/audio/podcasts/{$first->id}", [
            'title' => 'Edited', 'description' => 'First item', 'status' => 'draft',
            'media' => ['existing' => [
                'id' => $block->id, 'type' => 'text', 'body' => 'Overwritten',
            ]],
        ])->assertNotFound();
        $this->assertSame('Private', $block->fresh()->body);
    }
}
