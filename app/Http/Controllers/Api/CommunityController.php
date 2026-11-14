<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Models\CommunityReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $input = $request->validate([
            'mine' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);
        $posts = CommunityPost::query()
            ->with('author')
            ->withCount('replies')
            ->when($request->boolean('mine'), fn ($query) =>
                $query->where('user_id', $request->user()->id))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($input['per_page'] ?? 25);

        return response()->json([
            'data' => $posts->getCollection()->map(
                fn (CommunityPost $post) => $post->toMobileArray()
            ),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
                'per_page' => $posts->perPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'body' => ['required', 'string', 'max:100', function ($attribute, $value, $fail) {
                if (trim($value) === '') $fail('Write a post before publishing.');
            }],
        ]);

        $post = CommunityPost::create([
            'user_id' => $request->user()->id,
            'body' => trim($input['body']),
        ]);
        $post->load('author')->loadCount('replies');

        return response()->json(['data' => $post->toMobileArray()], 201);
    }

    public function show(CommunityPost $post, Request $request): JsonResponse
    {
        $input = $request->validate([
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $post->load('author')->loadCount('replies');
        $replies = $post->replies()
            ->with('author')->orderBy('created_at')->orderBy('id')
            ->paginate($input['per_page'] ?? 50);

        return response()->json([
            'data' => [
                'post' => $post->toMobileArray(),
                'replies' => $replies->getCollection()->map(
                    fn (CommunityReply $reply) => $reply->toMobileArray()
                ),
            ],
            'meta' => [
                'current_page' => $replies->currentPage(),
                'last_page' => $replies->lastPage(),
                'total' => $replies->total(),
            ],
        ]);
    }

    public function reply(Request $request, CommunityPost $post): JsonResponse
    {
        $input = $request->validate([
            'body' => ['required', 'string', 'max:1000', function ($attribute, $value, $fail) {
                if (trim($value) === '') $fail('Write a reply before submitting.');
            }],
        ]);

        $reply = $post->replies()->create([
            'user_id' => $request->user()->id,
            'body' => trim($input['body']),
        ]);
        $reply->load('author');

        return response()->json(['data' => $reply->toMobileArray()], 201);
    }

    public function destroy(Request $request, CommunityPost $post): JsonResponse
    {
        abort_unless($post->user_id === $request->user()->id, 403);
        $post->delete(); // Database cascade removes the discussion replies.
        return response()->json(['message' => 'Post removed.']);
    }

    public function destroyReply(
        Request $request, CommunityPost $post, CommunityReply $reply
    ): JsonResponse {
        abort_unless($reply->community_post_id === $post->id, 404);
        abort_unless($reply->user_id === $request->user()->id, 403);
        $reply->delete();

        return response()->json(['message' => 'Reply removed.']);
    }
}
