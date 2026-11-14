<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WeeklyChallenge;
use App\Services\MemberTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M3ChallengesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function createChallenge(bool $published, string $title = 'Seven Days of Gratitude'): WeeklyChallenge
    {
        $challenge = WeeklyChallenge::create([
            'title' => $title,
            'instructions' => 'Practice gratitude and kindness this week.',
            'published_at' => $published ? now() : null,
        ]);
        for ($day = 1; $day <= 7; $day++) {
            $challenge->days()->create([
                'day_number' => $day,
                'activity' => "Reflect and pray on Day {$day}.",
            ]);
        }
        return $challenge;
    }

    private function headers(User $member): array
    {
        $token = app(MemberTokenService::class)->issue($member)['token'];
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_admin_can_create_and_publish_a_seven_day_challenge(): void
    {
        $days = [];
        for ($i = 1; $i <= 7; $i++) $days[$i] = "Prayer activity {$i}";

        $this->actingAs($this->admin())
            ->post('/admin/challenges', [
                'title' => 'Walk in Faith',
                'instructions' => 'A little step every day.',
                'action' => 'publish',
                'days' => $days,
            ])
            ->assertRedirect('/admin/challenges');

        $this->assertDatabaseCount('weekly_challenges', 1);
        $challenge = WeeklyChallenge::firstOrFail();
        $this->assertNotNull($challenge->published_at);
        $this->assertCount(7, $challenge->days);
    }

    public function test_only_members_see_published_challenges_and_latest_is_current(): void
    {
        $first = $this->createChallenge(true, 'First');
        $first->forceFill(['published_at' => now()->subWeek()])->save();
        $second = $this->createChallenge(true, 'Latest');
        $this->createChallenge(false, 'Hidden draft');

        $this->getJson('/api/v1/challenges')->assertUnauthorized();

        $member = User::factory()->create();
        $this->getJson('/api/v1/challenges', $this->headers($member))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.0.is_current', true)
            ->assertJsonPath('data.1.id', $first->id)
            ->assertJsonPath('data.1.is_current', false);
    }

    public function test_member_checkboxes_are_idempotent_isolated_and_reversible(): void
    {
        $challenge = $this->createChallenge(true);
        $sarah = User::factory()->create();
        $alex = User::factory()->create();
        $sarahHeaders = $this->headers($sarah);
        $alexHeaders = $this->headers($alex);
        $path = "/api/v1/challenges/{$challenge->id}/days/3";

        $this->putJson($path, ['completed' => true], $sarahHeaders)
            ->assertOk()->assertJsonPath('data.completed_days', 1);
        $this->putJson($path, ['completed' => true], $sarahHeaders)
            ->assertOk()->assertJsonPath('data.completed_days', 1);

        $this->getJson("/api/v1/challenges/{$challenge->id}", $sarahHeaders)
            ->assertOk()
            ->assertJsonPath('data.completed_days', 1)
            ->assertJsonPath('data.days.2.completed', true);
        $this->getJson("/api/v1/challenges/{$challenge->id}", $alexHeaders)
            ->assertOk()->assertJsonPath('data.completed_days', 0)
            ->assertJsonPath('data.days.2.completed', false);

        $this->putJson($path, ['completed' => false], $sarahHeaders)
            ->assertOk()->assertJsonPath('data.completed_days', 0);
        $this->assertDatabaseCount('weekly_challenge_completions', 0);
    }

    public function test_editing_day_text_keeps_original_row_and_member_completion(): void
    {
        $challenge = $this->createChallenge(true);
        $member = User::factory()->create();
        $headers = $this->headers($member);
        $dayId = $challenge->days()->where('day_number', 1)->firstOrFail()->id;

        $this->putJson("/api/v1/challenges/{$challenge->id}/days/1", [
            'completed' => true,
        ], $headers)->assertOk();

        $days = [];
        for ($i = 1; $i <= 7; $i++) $days[$i] = "Updated daily text {$i}";
        $this->actingAs($this->admin())->patch(
            "/admin/challenges/{$challenge->id}", [
                'title' => 'Edited Seven Days',
                'instructions' => 'Updated instructions.',
                'days' => $days,
                'action' => 'publish',
            ]
        )->assertRedirect('/admin/challenges');

        $this->assertSame($dayId, $challenge->days()->where('day_number', 1)->firstOrFail()->id);
        $this->getJson("/api/v1/challenges/{$challenge->id}", $headers)
            ->assertOk()->assertJsonPath('data.completed_days', 1)
            ->assertJsonPath('data.days.0.text', 'Updated daily text 1')
            ->assertJsonPath('data.days.0.completed', true);
    }

    public function test_draft_is_private_and_member_cannot_manage_challenges(): void
    {
        $draft = $this->createChallenge(false);
        $user = User::factory()->create();
        $this->getJson("/api/v1/challenges/{$draft->id}", $this->headers($user))
            ->assertNotFound();
        $this->actingAs($user)->get('/admin/challenges')->assertForbidden();

        $this->actingAs($this->admin())->post('/admin/challenges', [
            'title' => 'Missing days', 'instructions' => 'Incomplete week',
            'action' => 'publish', 'days' => [1 => 'Day one'],
        ])->assertSessionHasErrors();
    }
}
