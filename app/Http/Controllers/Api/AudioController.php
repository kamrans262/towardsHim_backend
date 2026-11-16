<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AudioItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AudioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['sometimes', 'in:podcast,meditation,prayer'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);

        $query = AudioItem::query()->with('media')->published()->orderByDesc('published_at')->orderByDesc('id');

        if (! empty($data['category'])) {
            $query->where('category', $data['category']);
        }

        $results = $query->paginate($data['per_page'] ?? 20);

        return response()->json([
            'data' => $results->getCollection()->map(fn (AudioItem $audio) => $audio->toMobileArray()),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    public function show(AudioItem $audio): JsonResponse
    {
        abort_unless(
            $audio->is_published &&
                $audio->published_at && $audio->published_at->lte(now()),
            404
        );

        return response()->json(['data' => $audio->load('media')->toMobileArray()]);
    }
}
