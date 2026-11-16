<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AudioPublication;
use App\Models\MemberNotificationState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $input = $request->validate([
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);
        $userId = $request->user()->id;

        $publications = AudioPublication::query()
            ->join('audio_items', 'audio_items.id', '=', 'audio_publications.audio_item_id')
            ->leftJoin('member_notification_states as state', function ($join) use ($userId) {
                $join->on('state.audio_publication_id', '=', 'audio_publications.id')
                    ->where('state.user_id', '=', $userId);
            })
            ->where('audio_items.is_published', true)
            ->where('audio_items.published_at', '<=', now())
            ->whereNull('state.deleted_at')
            ->select([
                'audio_publications.id as publication_id',
                'audio_publications.published_at as notified_at',
                'audio_items.id as audio_id',
                'audio_items.title',
                'audio_items.description',
                'audio_items.category',
                'audio_items.artwork_path',
                'state.read_at',
            ])
            ->orderByDesc('audio_publications.published_at')
            ->orderByDesc('audio_publications.id')
            ->paginate($input['per_page'] ?? 30);

        return response()->json([
            'data' => $publications->getCollection()->map(fn ($item) => [
                'id' => (string) $item->publication_id,
                'content_id' => (string) $item->audio_id,
                'title' => $item->title,
                'description' => $item->description,
                'category' => ucfirst($item->category),
                'artwork_url' => $item->artwork_path
                    ? asset('storage/'.$item->artwork_path) : null,
                'created_at' => \Illuminate\Support\Carbon::parse($item->notified_at, 'UTC')->toIso8601String(),
                'is_read' => $item->read_at !== null,
            ]),
            'meta' => [
                'current_page' => $publications->currentPage(),
                'last_page' => $publications->lastPage(),
                'total' => $publications->total(),
            ],
        ]);
    }

    public function markRead(Request $request, AudioPublication $publication): JsonResponse
    {
        $this->assertVisible($publication);
        $this->saveState($request->user()->id, $publication->id, 'read_at');

        return response()->json(['message' => 'Notification read.']);
    }

    public function destroy(Request $request, AudioPublication $publication): JsonResponse
    {
        $this->assertVisible($publication);
        $this->saveState($request->user()->id, $publication->id, 'deleted_at');

        return response()->json(['message' => 'Notification removed.']);
    }

    private function assertVisible(AudioPublication $publication): void
    {
        $audio = $publication->audio;
        abort_unless(
            $audio && $audio->is_published &&
            $audio->published_at && $audio->published_at->lte(now()),
            404
        );
    }

    private function saveState(int $userId, int $publicationId, string $field): void
    {
        DB::table('member_notification_states')->upsert([[
            'user_id' => $userId,
            'audio_publication_id' => $publicationId,
            $field => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['user_id', 'audio_publication_id'], [$field, 'updated_at']);
    }
}
