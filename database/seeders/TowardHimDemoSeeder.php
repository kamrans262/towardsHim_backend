<?php

namespace Database\Seeders;

use App\Models\AudioItem;
use App\Models\AudioPublication;
use App\Models\CommunityPost;
use App\Models\CommunityReply;
use App\Models\PlatformContent;
use App\Models\User;
use App\Models\WeeklyChallenge;
use App\Models\WeeklyChallengeCompletion;
use App\Models\WeeklyChallengeDay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class TowardHimDemoSeeder extends Seeder
{
    private const SENTINEL_EMAIL = 'ava.mitchell.demo@example.test';

    /**
     * Explicit opt-in only: php artisan db:seed --class=TowardHimDemoSeeder --force.
     * Leaves the existing administrator untouched and sends no email/push.
     */
    public function run(): void
    {
        $admin = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $admin) {
            throw new RuntimeException('Demo seed aborted: create an active admin account before seeding.');
        }

        // Do not re-create deleted/moderated demo content on subsequent runs.
        if (User::query()->where('email', self::SENTINEL_EMAIL)->exists()) {
            $this->command?->info('Toward HIM demo data already exists; no changes made.');

            return;
        }

        // Real spoken audio was not supplied. Generate clearly labeled,
        // original instrumental WAV clips for end-to-end playback testing.
        $fixtures = $this->createPlaybackFixtures();

        DB::transaction(function () use ($admin, $fixtures): void {
            $members = $this->createMembers();
            $this->createChallenges($admin, $members);
            $this->createCommunity($members);
            $this->createAudio($admin, $fixtures);

            PlatformContent::query()->firstOrCreate(
                ['id' => 1],
                PlatformContent::DEFAULTS
            );
        });

        $this->command?->info(
            'Toward HIM demo seeded: 8 fictional members, 4 seven-day challenges, '
            .'8 posts with 16 replies, 3 playable audio demos, 6 audio drafts '
            .'and 3 in-app audio notifications.'
        );
        $this->command?->warn(
            'Fictional members use example.test email addresses and random passwords. '
            .'Published audio is instrumental TEST content, not final recordings.'
        );
    }

    /** @return array<int, User> */
    private function createMembers(): array
    {
        $people = [
            ['Ava Mitchell', 'ava.mitchell.demo@example.test', 'Growing in gratitude and daily prayer.', true],
            ['Daniel Brooks', 'daniel.brooks.demo@example.test', 'Learning to make quiet time each morning.', true],
            ['Grace Turner', 'grace.turner.demo@example.test', 'Thankful for a supportive faith community.', true],
            ['Noah Bennett', 'noah.bennett.demo@example.test', 'Practicing a little more patience every day.', true],
            ['Hannah Lewis', 'hannah.lewis.demo@example.test', 'Finding peace in thoughtful reflection.', true],
            ['Olivia Reed', 'olivia.reed.demo@example.test', 'Making time for prayer and encouragement.', true],
            ['Elijah Parker', 'elijah.parker.demo@example.test', 'Taking one step forward every week.', true],
            ['Emma Collins', 'emma.collins.demo@example.test', 'On a journey toward a stronger faith.', false],
        ];

        $members = [];
        foreach ($people as [$name, $email, $bio, $active]) {
            $member = new User();
            $member->forceFill([
                'name' => $name,
                'email' => $email,
                'bio' => $bio,
                'password' => Hash::make(Str::random(64)),
                'role' => 'member',
                'is_active' => $active,
                'email_verified_at' => null,
            ])->save();
            $members[] = $member;
        }

        return $members;
    }

    /** @param array<int, User> $members */
    private function createChallenges(User $admin, array $members): void
    {
        $weekly = [
            [
                'title' => '[Demo] Seven Days of Gratitude',
                'instructions' => 'Each day, pause, notice one gift, and put your gratitude into action.',
                'published_at' => now()->subDays(21),
                'days' => [
                    'Write down three things you are grateful for.',
                    'Thank someone for a kindness they showed you.',
                    'Spend five quiet minutes noticing everyday blessings.',
                    'Read an encouraging passage and reflect on its message.',
                    'Help someone without expecting anything in return.',
                    'Share one hopeful thought with a friend.',
                    'Review the week and keep one gratitude habit.',
                ],
            ],
            [
                'title' => '[Demo] Finding Peace Through Prayer',
                'instructions' => 'Build a short, steady habit of prayer and reflection over seven days.',
                'published_at' => now()->subDays(14),
                'days' => [
                    'Begin your morning with two minutes of prayer.',
                    'Name one worry and entrust it to God.',
                    'Take a mindful walk without distractions.',
                    'Pray for a person who needs encouragement.',
                    'Write a peaceful thought in your journal.',
                    'Spend ten minutes in silent reflection.',
                    'Reflect on moments of peace from this week.',
                ],
            ],
            [
                'title' => '[Demo] Closer to HIM, One Day at a Time',
                'instructions' => 'A seven-day journey of prayer, kindness, scripture, and reflection.',
                'published_at' => now(),
                'days' => [
                    'Set aside five minutes for prayer today.',
                    'Read a short scripture passage and note one lesson.',
                    'Offer a genuine word of encouragement.',
                    'Pause before reacting and choose patience.',
                    'Reach out to someone who may feel alone.',
                    'Practice gratitude for three moments today.',
                    'Reflect on your journey and choose a habit to continue.',
                ],
            ],
            [
                'title' => '[Demo] Strength in Everyday Faith',
                'instructions' => 'An unpublished challenge ready for admin review and editing.',
                'published_at' => null,
                'days' => [
                    'Begin the day with a hopeful intention.',
                    'Take a short walk and reflect quietly.',
                    'Offer help to someone in need.',
                    'Write about a moment that tested your patience.',
                    'Share something uplifting with a friend.',
                    'Spend time reading and reflecting.',
                    'Choose one intention for the coming week.',
                ],
            ],
        ];

        foreach ($weekly as $index => $item) {
            $challenge = WeeklyChallenge::query()->create([
                'title' => $item['title'],
                'instructions' => $item['instructions'],
                'published_at' => $item['published_at'],
                'created_by' => $admin->id,
            ]);

            foreach ($item['days'] as $dayIndex => $activity) {
                WeeklyChallengeDay::query()->create([
                    'weekly_challenge_id' => $challenge->id,
                    'day_number' => $dayIndex + 1,
                    'activity' => $activity,
                ]);
            }

            if ($index === 3) continue; // Drafts have no member progress.

            $days = $challenge->days()->get();
            foreach ($members as $memberIndex => $member) {
                if (! $member->is_active) continue;
                $count = $index === 0
                    ? 7 - ($memberIndex % 3)
                    : ($index === 1 ? 3 + ($memberIndex % 4) : 1 + ($memberIndex % 5));
                foreach ($days->take($count) as $day) {
                    WeeklyChallengeCompletion::query()->create([
                        'user_id' => $member->id,
                        'weekly_challenge_day_id' => $day->id,
                    ]);
                }
            }
        }
    }

    /** @param array<int, User> $members */
    private function createCommunity(array $members): void
    {
        $threads = [
            ['What is one small thing that brought you peace today?',
                'I found peace by taking a quiet walk after work.',
                'A few minutes away from my phone helped me too.'],
            ['This week I am focusing on practicing gratitude.',
                'Writing down three blessings every day has helped me.',
                'That is a lovely habit to build!'],
            ['Does anyone have a favorite time of day for prayer?',
                'Early mornings work best for me.',
                'I like evenings when the house is quiet.'],
            ['I completed my first Toward HIM 3 challenge day!',
                'Well done! One day at a time.',
                'Keep going. Small steps really do matter.'],
            ['Today I am choosing patience over rushing.',
                'A gentle reminder I needed this morning.',
                'Being intentional makes a big difference.'],
            ['Sending encouragement to anyone having a difficult week.',
                'Thank you. I needed to read this today.',
                'We are walking this journey together.'],
            ['A quiet moment can change the direction of a busy day.',
                'I am learning to slow down and listen.',
                'Even five minutes can feel restorative.'],
            ['What is one habit you want to carry into next week?',
                'Reading something uplifting each morning.',
                'I want to keep a gratitude journal.'],
        ];

        foreach ($threads as $index => [$body, $firstReply, $secondReply]) {
            $postedAt = now()->subDays(7 - $index)->addHours(10);
            $post = new CommunityPost();
            $post->forceFill([
                'user_id' => $members[$index]->id,
                'body' => $body,
                'created_at' => $postedAt,
                'updated_at' => $postedAt,
            ])->save();

            foreach ([$firstReply, $secondReply] as $offset => $replyText) {
                $repliedAt = $postedAt->copy()->addMinutes(($offset + 1) * 35);
                $reply = new CommunityReply();
                $reply->forceFill([
                    'community_post_id' => $post->id,
                    'user_id' => $members[($index + $offset + 1) % count($members)]->id,
                    'body' => $replyText,
                    'created_at' => $repliedAt,
                    'updated_at' => $repliedAt,
                ])->save();
            }
        }
    }

    /** @param array<string, string> $fixtures */
    private function createAudio(User $admin, array $fixtures): void
    {
        $published = [
            ['podcast', '[Demo] Podcast Audio Playback Test',
                'Instrumental sample to verify podcast playback. Not a recorded talk; replace before launch.', 20, 1],
            ['meditation', '[Demo] Peaceful Reflection Soundscape',
                'Original instrumental tones for testing meditation streaming and seeking. Temporary demo only.', 24, 2],
            ['prayer', '[Demo] Quiet Prayer Audio Playback Test',
                'Instrumental sample for testing Prayer playback. Replace with a real prayer recording.', 28, 3],
        ];

        foreach ($published as [$category, $title, $description, $seconds, $daysAgo]) {
            $audio = AudioItem::query()->create([
                'category' => $category,
                'title' => $title,
                'description' => $description,
                'audio_path' => $fixtures[$category],
                'duration_seconds' => $seconds,
                'is_published' => true,
                'published_at' => now()->subDays($daysAgo),
                'created_by' => $admin->id,
            ]);

            // In-app notifications only; never dispatch AudioPublished/push.
            AudioPublication::query()->create([
                'audio_item_id' => $audio->id,
                'published_at' => $audio->published_at,
            ]);
        }

        $drafts = [
            ['podcast', '[Demo Draft] Choosing Faith Every Morning',
                'A conversation about starting the day with intention and hope.', 900],
            ['podcast', '[Demo Draft] Gratitude in Difficult Seasons',
                'Practicing gratitude when life feels challenging.', 1080],
            ['meditation', '[Demo Draft] Five Minutes of Stillness',
                'A guided practice focusing on breathing and calm.', 300],
            ['meditation', '[Demo Draft] Rest and Renewal',
                'A gentle reflection on rest, patience, and peace.', 480],
            ['prayer', '[Demo Draft] Prayer for a New Day',
                'A morning prayer for wisdom, kindness, and strength.', 240],
            ['prayer', '[Demo Draft] Prayer for Peace',
                'A reflective prayer for comfort and hope.', 360],
        ];

        foreach ($drafts as [$category, $title, $description, $seconds]) {
            AudioItem::query()->create([
                'category' => $category,
                'title' => $title,
                'description' => $description,
                'duration_seconds' => $seconds,
                'is_published' => false,
                'audio_path' => null,
                'published_at' => null,
                'created_by' => $admin->id,
            ]);
        }
    }

    /** @return array<string, string> */
    private function createPlaybackFixtures(): array
    {
        $disk = Storage::disk('public');
        $specs = [
            'podcast' => ['demo/toward-him-podcast-preview.wav', 20, 196],
            'meditation' => ['demo/toward-him-meditation-preview.wav', 24, 220],
            'prayer' => ['demo/toward-him-prayer-preview.wav', 28, 246],
        ];

        $paths = [];
        foreach ($specs as $category => [$path, $seconds, $pitch]) {
            if (! $disk->exists($path)) {
                if (! $disk->put($path, $this->generateTone((int) $seconds, (int) $pitch))) {
                    throw new RuntimeException('Could not write demo audio: '.$path);
                }
            }
            $paths[$category] = $path;
        }
        return $paths;
    }

    /**
     * Original mono PCM WAV generated without third-party recordings,
     * downloads, providers, or extra server dependencies.
     */
    private function generateTone(int $seconds, int $frequency): string
    {
        $sampleRate = 8000;
        $sampleCount = $seconds * $sampleRate;
        $samples = '';
        $notes = [1.0, 1.25, 1.5, 1.125];

        for ($i = 0; $i < $sampleCount; $i++) {
            $time = $i / $sampleRate;
            $fade = min(1.0, $time / 1.5, ($seconds - $time) / 2.0);
            $note = $notes[intdiv($i, $sampleRate * 5) % count($notes)];
            $fundamental = 2.0 * M_PI * $frequency * $note * $time;
            $sound = 0.26 * sin($fundamental)
                + 0.09 * sin($fundamental * 1.5)
                + 0.06 * sin($fundamental * 0.5);
            $value = (int) round($sound * $fade * 25000);
            $samples .= pack('v', $value & 0xffff);
        }

        $bytes = strlen($samples);

        return 'RIFF'.pack('V', $bytes + 36).'WAVE'
            .'fmt '.pack('V', 16).pack('v', 1).pack('v', 1)
            .pack('V', $sampleRate).pack('V', $sampleRate * 2)
            .pack('v', 2).pack('v', 16)
            .'data'.pack('V', $bytes).$samples;
    }
}
