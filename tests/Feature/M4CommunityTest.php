<?php

namespace Tests\Feature;

use App\Models\CommunityPost;
use App\Models\CommunityReply;
use App\Models\User;
use App\Services\MemberTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M4CommunityTest extends TestCase
{
    use RefreshDatabase;

    private function credentials(User $user): array
    {
        return ['Authorization' => 'Bearer '.app(MemberTokenService::class)->issue($user)['token']];
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        return $admin;
    }

    public function test_guests_cannot_read_or_create_discussions(): void
    {
        $this->getJson('/api/v1/community/posts')->assertUnauthorized();
        $this->postJson('/api/v1/community/posts', ['body' => 'Hello'])->assertUnauthorized();
    }

    public function test_feed_newest_first_my_posts_and_member_author_details(): void
    {
        $sarah = User::factory()->create(['name' => 'Sarah Johnson']);
        $alex = User::factory()->create(['name' => 'Alex Member']);
        $first = CommunityPost::create(['user_id' => $alex->id, 'body' => 'First community post']);
        $second = CommunityPost::create(['user_id' => $sarah->id, 'body' => 'A second post']);

        $this->getJson('/api/v1/community/posts', $this->credentials($sarah))
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', (string) $second->id)
            ->assertJsonPath('data.0.author_name', 'Sarah Johnson')
            ->assertJsonPath('data.1.id', (string) $first->id);

        $this->getJson('/api/v1/community/posts?mine=1', $this->credentials($sarah))
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'A second post');
    }

    public function test_member_posts_trimmed_text_and_replies_are_persisted(): void
    {
        $sarah = User::factory()->create();
        $alex = User::factory()->create();

        $created = $this->postJson('/api/v1/community/posts', [
            'body' => '  Today I am grateful.  ',
        ], $this->credentials($sarah))
            ->assertCreated()->assertJsonPath('data.body', 'Today I am grateful.');
        $id = $created->json('data.id');

        $this->postJson("/api/v1/community/posts/{$id}/replies", [
            'body' => '  Amen.  ',
        ], $this->credentials($alex))
            ->assertCreated()->assertJsonPath('data.body', 'Amen.');

        $this->getJson("/api/v1/community/posts/{$id}", $this->credentials($sarah))
            ->assertOk()->assertJsonPath('data.post.reply_count', 1)
            ->assertJsonPath('data.replies.0.body', 'Amen.')
            ->assertJsonPath('meta.total', 1);

        $this->assertDatabaseCount('community_posts', 1);
        $this->assertDatabaseCount('community_replies', 1);
    }

    public function test_validation_and_cross_member_deletion_are_enforced(): void
    {
        $sarah = User::factory()->create();
        $alex = User::factory()->create();
        $post = CommunityPost::create(['user_id' => $sarah->id, 'body' => 'Encouragement']);
        $reply = CommunityReply::create([
            'community_post_id' => $post->id,
            'user_id' => $alex->id,
            'body' => 'Agreed',
        ]);

        $this->postJson('/api/v1/community/posts', [
            'body' => str_repeat('x', 101),
        ], $this->credentials($sarah))->assertUnprocessable();
        $this->postJson("/api/v1/community/posts/{$post->id}/replies", [
            'body' => '  ',
        ], $this->credentials($sarah))->assertUnprocessable();

        $this->deleteJson("/api/v1/community/posts/{$post->id}", [], $this->credentials($alex))
            ->assertForbidden();
        $this->deleteJson(
            "/api/v1/community/posts/{$post->id}/replies/{$reply->id}", [],
            $this->credentials($sarah)
        )->assertForbidden();

        $this->assertDatabaseHas('community_posts', ['id' => $post->id]);
        $this->assertDatabaseHas('community_replies', ['id' => $reply->id]);
    }

    public function test_admin_moderation_removes_reply_separately_or_post_with_replies(): void
    {
        $member = User::factory()->create();
        $post = CommunityPost::create(['user_id' => $member->id, 'body' => 'A post']);
        $first = CommunityReply::create([
            'community_post_id' => $post->id, 'user_id' => $member->id, 'body' => 'First',
        ]);
        $second = CommunityReply::create([
            'community_post_id' => $post->id, 'user_id' => $member->id, 'body' => 'Second',
        ]);

        $this->get('/admin/community')->assertRedirect('/');
        $this->actingAs($member)->get('/admin/community')->assertForbidden();

        $this->actingAs($this->admin())->get('/admin/community')->assertOk();
        $this->get("/admin/community/{$post->id}")->assertOk();
        $this->delete("/admin/community/{$post->id}/replies/{$first->id}")->assertRedirect();
        $this->assertDatabaseMissing('community_replies', ['id' => $first->id]);
        $this->assertDatabaseHas('community_replies', ['id' => $second->id]);

        $this->delete("/admin/community/{$post->id}")->assertRedirect('/admin/community');
        $this->assertDatabaseMissing('community_posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('community_replies', ['id' => $second->id]);
    }

    public function test_member_deletion_of_post_cascades_and_wrong_thread_is_not_accepted(): void
    {
        $user = User::factory()->create();
        $first = CommunityPost::create(['user_id' => $user->id, 'body' => 'First']);
        $second = CommunityPost::create(['user_id' => $user->id, 'body' => 'Second']);
        $reply = CommunityReply::create([
            'community_post_id' => $first->id, 'user_id' => $user->id, 'body' => 'Reply',
        ]);
        $headers = $this->credentials($user);

        $this->deleteJson(
            "/api/v1/community/posts/{$second->id}/replies/{$reply->id}", [], $headers
        )->assertNotFound();

        $this->deleteJson("/api/v1/community/posts/{$first->id}", [], $headers)->assertOk();
        $this->assertDatabaseCount('community_replies', 0);
        $this->assertDatabaseCount('community_posts', 1);
    }
}
