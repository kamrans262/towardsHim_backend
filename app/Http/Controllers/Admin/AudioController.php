<?php

namespace App\Http\Controllers\Admin;

use App\Events\AudioPublished;
use App\Http\Controllers\Controller;
use App\Models\AudioItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AudioController extends Controller
{
    private const SLUGS = [
        'podcasts' => 'podcast',
        'meditations' => 'meditation',
        'prayers' => 'prayer',
    ];

    private function type(string $category): string
    {
        abort_unless(isset(self::SLUGS[$category]), 404);
        return self::SLUGS[$category];
    }

    private function entry(string $category, AudioItem $audio): AudioItem
    {
        abort_unless($audio->category === $this->type($category), 404);
        return $audio;
    }

    public function index(string $category): View
    {
        $type = $this->type($category);

        return view('admin.audio.index', [
            'category' => $category,
            'label' => ucfirst($category),
            'items' => AudioItem::where('category', $type)->latest()->paginate(15),
        ]);
    }

    public function create(string $category): View
    {
        $this->type($category);
        return view('admin.audio.form', [
            'category' => $category,
            'label' => ucfirst($category),
            'item' => new AudioItem(),
        ]);
    }

    public function edit(string $category, AudioItem $audio): View
    {
        return view('admin.audio.form', [
            'category' => $category,
            'label' => ucfirst($category),
            'item' => $this->entry($category, $audio),
        ]);
    }

    public function store(Request $request, string $category): RedirectResponse
    {
        $type = $this->type($category);
        $input = $this->validated($request, true);

        $audio = new AudioItem(['category' => $type]);
        $this->saveItem($request, $audio, $input);

        return redirect()->route('admin.audio.index', $category)
            ->with('success', 'Audio item saved successfully.');
    }

    public function update(Request $request, string $category, AudioItem $audio): RedirectResponse
    {
        $audio = $this->entry($category, $audio);
        $input = $this->validated($request, false);
        $this->saveItem($request, $audio, $input);

        return redirect()->route('admin.audio.index', $category)
            ->with('success', 'Audio item updated successfully.');
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'duration_minutes' => ['nullable', 'integer', 'between:1,720'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'audio' => [$creating ? 'nullable' : 'sometimes', 'file',
                'mimes:mp3,m4a,mp4,aac,ogg,wav', 'max:102400'],
            'artwork' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_artwork' => ['nullable', 'boolean'],
        ]);
    }

    private function saveItem(Request $request, AudioItem $item, array $input): void
    {
        if ($input['status'] === 'published' && ! $request->hasFile('audio') && ! $item->audio_path) {
            throw ValidationException::withMessages(['audio' => 'Upload an audio file before publishing.']);
        }

        $freshFiles = [];
        $oldFiles = [];
        try {
            if ($request->hasFile('audio')) {
                $freshFiles[] = $item->audio_path = $request->file('audio')->store('audio', 'public');
                if ($item->exists && $item->getOriginal('audio_path')) {
                    $oldFiles[] = $item->getOriginal('audio_path');
                }
            }
            if ($request->hasFile('artwork')) {
                $freshFiles[] = $item->artwork_path = $request->file('artwork')->store('artwork', 'public');
                if ($item->exists && $item->getOriginal('artwork_path')) {
                    $oldFiles[] = $item->getOriginal('artwork_path');
                }
            } elseif ($request->boolean('remove_artwork') && $item->artwork_path) {
                $oldFiles[] = $item->artwork_path;
                $item->artwork_path = null;
            }

            $firstPublication = $input['status'] === 'published' && ! $item->is_published;
            $item->fill([
                'title' => $input['title'],
                'description' => $input['description'],
                'duration_seconds' => isset($input['duration_minutes']) ?
                    (int) $input['duration_minutes'] * 60 : null,
                'is_published' => $input['status'] === 'published',
                'published_at' => $firstPublication ? now() : ($input['status'] === 'draft' ? null : $item->published_at),
                'created_by' => $item->created_by ?? $request->user()->id,
            ]);

            DB::transaction(fn () => $item->save());

            foreach ($oldFiles as $path) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            foreach ($freshFiles as $path) {
                if ($path) Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        // Dispatch after the file and record are saved; future listeners are
        // responsible for their own retries, not deleting a published file.
        if ($firstPublication) {
            AudioPublished::dispatch($item->fresh());
        }
    }

    public function destroy(string $category, AudioItem $audio): RedirectResponse
    {
        $audio = $this->entry($category, $audio);
        $paths = array_filter([$audio->audio_path, $audio->artwork_path]);

        $audio->delete();
        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('admin.audio.index', $category)
            ->with('success', 'Audio item removed.');
    }
}
