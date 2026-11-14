<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeeklyChallenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WeeklyChallengeController extends Controller
{
    public function index(): View
    {
        return view('admin.challenges.index', [
            'current' => WeeklyChallenge::published()
                ->orderByDesc('published_at')->orderByDesc('id')->first(),
            'previous' => WeeklyChallenge::published()
                ->orderByDesc('published_at')->orderByDesc('id')->get()->skip(1),
            'drafts' => WeeklyChallenge::whereNull('published_at')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.challenges.form', [
            'challenge' => new WeeklyChallenge(),
            'dayTexts' => [],
        ]);
    }

    public function edit(WeeklyChallenge $challenge): View
    {
        return view('admin.challenges.form', [
            'challenge' => $challenge,
            'dayTexts' => $challenge->days()->pluck('activity', 'day_number')->all(),
        ]);
    }

    private function validated(Request $request): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:180'],
            'instructions' => ['required', 'string', 'max:10000'],
            'action' => ['required', 'in:draft,publish'],
            'days' => ['required', 'array', 'size:7'],
        ];

        for ($day = 1; $day <= 7; $day++) {
            $rules["days.$day"] = ['required', 'string', 'max:5000'];
        }

        return $request->validate($rules);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        DB::transaction(function () use ($validated, $request): void {
            $challenge = WeeklyChallenge::create([
                'title' => $validated['title'],
                'instructions' => $validated['instructions'],
                'published_at' => $validated['action'] === 'publish' ? now() : null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['days'] as $number => $activity) {
                $challenge->days()->create([
                    'day_number' => (int) $number,
                    'activity' => $activity,
                ]);
            }
        });

        return redirect()->route('admin.challenges.index')
            ->with('success', 'Challenge created successfully.');
    }

    public function update(Request $request, WeeklyChallenge $challenge): RedirectResponse
    {
        $validated = $this->validated($request);
        DB::transaction(function () use ($validated, $challenge): void {
            $changes = [
                'title' => $validated['title'],
                'instructions' => $validated['instructions'],
            ];

            // Publishing is irreversible: historical challenges remain accessible.
            // An edit must never reset the publication timestamp or a day row ID.
            if (! $challenge->published_at && $validated['action'] === 'publish') {
                $changes['published_at'] = now();
            }

            $challenge->update($changes);

            foreach ($validated['days'] as $number => $activity) {
                $challenge->days()->updateOrCreate(
                    ['day_number' => (int) $number],
                    ['activity' => $activity]
                );
            }
        });

        return redirect()->route('admin.challenges.index')
            ->with('success', 'Challenge saved. Existing member progress is preserved.');
    }
}
