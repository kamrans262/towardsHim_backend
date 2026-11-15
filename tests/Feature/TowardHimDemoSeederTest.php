<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TowardHimDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TowardHimDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_only_demo_content_and_preserves_the_existing_admin(): void
    {
        Storage::fake('public');

        $admin = new User();
        $admin->forceFill([
            'name' => 'Existing Administrator',
            'email' => 'kamrans262@gmail.com',
            'password' => Hash::make('unmodified-admin-password'),
            'role' => 'admin',
            'is_active' => true,
        ])->save();
        $passwordHash = $admin->password;

        $this->seed(TowardHimDemoSeeder::class);

        $this->assertDatabaseCount('users', 9);
        $this->assertDatabaseCount('weekly_challenges', 4);
        $this->assertDatabaseCount('weekly_challenge_days', 28);
        $this->assertDatabaseCount('community_posts', 8);
        $this->assertDatabaseCount('community_replies', 16);
        $this->assertDatabaseCount('audio_items', 9);
        $this->assertDatabaseCount('audio_publications', 3);
        $this->assertDatabaseCount('platform_contents', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'kamrans262@gmail.com',
            'name' => 'Existing Administrator',
            'role' => 'admin',
        ]);
        $this->assertSame($passwordHash, $admin->fresh()->password);

        foreach (['podcast', 'meditation', 'prayer'] as $category) {
            $path = "demo/toward-him-{$category}-preview.wav";
            Storage::disk('public')->assertExists($path);
            $this->assertStringStartsWith('RIFF', Storage::disk('public')->get($path));
        }

        $this->seed(TowardHimDemoSeeder::class);
        $this->assertDatabaseCount('users', 9);
        $this->assertDatabaseCount('audio_items', 9);
        $this->assertDatabaseCount('audio_publications', 3);
        $this->assertDatabaseCount('community_posts', 8);
        $this->assertDatabaseCount('weekly_challenge_days', 28);
    }

    public function test_it_refuses_to_seed_without_the_named_admin(): void
    {
        Storage::fake('public');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('administrator kamrans262@gmail.com was not found');
        $this->seed(TowardHimDemoSeeder::class);
    }
}
