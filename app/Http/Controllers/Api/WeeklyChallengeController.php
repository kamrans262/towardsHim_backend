<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeeklyChallenge;
use App\Models\WeeklyChallengeDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeeklyChallengeController extends Controller
{
    private function currentChallengeId(): int
    {
        return (int) (WeeklyChallenge::published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->value('id') ?? 0);
    }

    private function published(WeeklyChallenge $challenge): void
    {
        abort_unless(
            $challenge->published_at && $challenge->published_at->lte(now()),
            404
        );
    }

    public function index(Request $request): JsonResponse
    {
        $memberId = $request->user()->id;
        $currentId = $this->currentChallengeId();

        $items = WeeklyChallenge::query()
            ->published()
            ->withCount([
                'days as completed_days' => fn ($query) => $query
                    ->whereHas('completions', fn ($sub) => $sub->where('user_id', $memberId)),
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $items->map(fn (WeeklyChallenge $item) => $item->toMobileArray($currentId)),
        ]);
    }

    public function show(Request $request, WeeklyChallenge $challenge): JsonResponse
    {
        $this->published($challenge);
        $memberId = $request->user()->id;
        $challenge->load([
            'days.completions' => fn ($query) => $query->where('user_id', $memberId),
        ])->withMemberProgress($memberId);

        return response()->json([
            'data' => $challenge->toMobileArray($this->currentChallengeId(), true),
        ]);
    }

    public function setCompletion(
        Request $request,
        WeeklyChallenge $challenge,
        int $day
    ): JsonResponse {
        $this->published($challenge);
        abort_unless($day >= 1 && $day <= 7, 404);
        $data = $request->validate(['completed' => ['required', 'boolean']]);
        $challengeDay = $challenge->days()->where('day_number', $day)->firstOrFail();
        $memberId = $request->user()->id;

        DB::transaction(function () use ($data, $challengeDay, $memberId): void {
            $key = [
                'user_id' => $memberId,
                'weekly_challenge_day_id' => $challengeDay->id,
            ];
            if ($data['completed']) {
                DB::table('weekly_challenge_completions')->insertOrIgnore([
                    ...$key, 'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                DB::table('weekly_challenge_completions')->where($key)->delete();
            }
        });

        $completed = WeeklyChallengeDay::query()
            ->where('weekly_challenge_id', $challenge->id)
            ->whereHas('completions', fn ($query) => $query->where('user_id', $memberId))
            ->count();

        return response()->json([
            'data' => [
                'day' => $day,
                'completed' => (bool) $data['completed'],
                'completed_days' => $completed,
                'total_days' => 7,
            ],
        ]);
    }
}
