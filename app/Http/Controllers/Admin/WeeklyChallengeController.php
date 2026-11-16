<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeeklyChallenge;
use App\Services\ContentMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
            'dayRecords' => [],
        ]);
    }

    public function edit(WeeklyChallenge $challenge): View
    {
        return view('admin.challenges.form', [
            'challenge' => $challenge->load(['media', 'days.media']),
            'dayTexts' => $challenge->days->pluck('activity', 'day_number')->all(),
            'dayRecords' => $challenge->days->keyBy('day_number'),
        ]);
    }

    private function validated(Request $request): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:180'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'action' => ['required', 'in:draft,publish'],
            'days' => ['required', 'array', 'size:7'],
            ...ContentMediaService::rules('media'),
        ];

        for ($day = 1; $day <= 7; $day++) {
            $rules["days.$day"] = ['nullable', 'string', 'max:5000'];
            $rules += ContentMediaService::rules("day_media.$day");
        }

        return $request->validate($rules);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $media = new ContentMediaService();
        try {
            DB::transaction(function () use ($validated, $request, $media): void {
                $challenge = WeeklyChallenge::create([
                    'title' => $validated['title'],
                    'instructions' => $validated['instructions'] ?? '',
                    'published_at' => $validated['action'] === 'publish' ? now() : null,
                    'created_by' => $request->user()->id,
                ]);

                $media->sync($challenge, $validated['media'] ?? []);
                foreach ($validated['days'] as $number => $activity) {
                    $day = $challenge->days()->create([
                        'day_number' => (int) $number,
                        'activity' => $activity ?? '',
                    ]);
                    $media->sync(
                        $day,
                        $validated['day_media'][$number] ?? [],
                        "day_media.$number"
                    );
                }
                $this->assertContentPresent($challenge, $validated['action'] === 'publish');
            });
            $media->committed();
        } catch (\Throwable $error) {
            $media->failed();
            throw $error;
        }

        return redirect()->route('admin.challenges.index')
            ->with('success', 'Challenge created successfully.');
    }

    public function update(Request $request, WeeklyChallenge $challenge): RedirectResponse
    {
        $validated = $this->validated($request);
        $media = new ContentMediaService();
        try {
            DB::transaction(function () use ($validated, $challenge, $media): void {
                $changes = [
                    'title' => $validated['title'],
                    'instructions' => $validated['instructions'] ?? '',
                ];
                // Published history retains its timestamp and completed-day IDs.
                if (! $challenge->published_at && $validated['action'] === 'publish') {
                    $changes['published_at'] = now();
                }
                $challenge->update($changes);
                $media->sync($challenge, $validated['media'] ?? []);

                foreach ($validated['days'] as $number => $activity) {
                    $day = $challenge->days()->updateOrCreate(
                        ['day_number' => (int) $number],
                        ['activity' => $activity ?? '']
                    );
                    $media->sync(
                        $day,
                        $validated['day_media'][$number] ?? [],
                        "day_media.$number"
                    );
                }
                $this->assertContentPresent($challenge, $challenge->published_at !== null);
            });
            $media->committed();
        } catch (\Throwable $error) {
            $media->failed();
            throw $error;
        }

        return redirect()->route('admin.challenges.index')
            ->with('success', 'Challenge saved. Existing member progress is preserved.');
    }

    private function assertContentPresent(WeeklyChallenge $challenge, bool $published): void
    {
        if (! $published) return;

        if (! trim($challenge->instructions) && ! $challenge->media()->exists()) {
            throw ValidationException::withMessages([
                'instructions' => 'Add text or media to the main challenge before publishing.',
            ]);
        }
        $days = $challenge->days()->with('media')->get();
        if ($days->count() !== 7) {
            throw ValidationException::withMessages(['days' => 'All seven days are required.']);
        }
        foreach ($days as $day) {
            if (! trim($day->activity) && $day->media->isEmpty()) {
                throw ValidationException::withMessages([
                    'days.'.$day->day_number => 'Add text or media for Day '.$day->day_number.'.',
                ]);
            }
        }
    }
}
