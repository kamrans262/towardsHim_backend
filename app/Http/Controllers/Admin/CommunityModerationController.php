<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Models\CommunityReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CommunityModerationController extends Controller
{
    public function index(): View
    {
        return view('admin.community.index', [
            'posts' => CommunityPost::query()
                ->with('author')->withCount('replies')
                ->orderByDesc('created_at')->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    public function show(CommunityPost $post): View
    {
        $post->load('author')->loadCount('replies');
        return view('admin.community.show', [
            'post' => $post,
            'replies' => $post->replies()
                ->with('author')->orderBy('created_at')->orderBy('id')
                ->paginate(30),
        ]);
    }

    public function destroy(CommunityPost $post): RedirectResponse
    {
        $post->delete();
        return redirect()->route('admin.community.index')
            ->with('success', 'Post and its replies were removed.');
    }

    public function destroyReply(CommunityPost $post, CommunityReply $reply): RedirectResponse
    {
        abort_unless($reply->community_post_id === $post->id, 404);
        $reply->delete();
        return redirect()->route('admin.community.show', [$post, 'page' => request('page', 1)])
            ->with('success', 'Reply removed. Other replies remain available.');
    }
}
