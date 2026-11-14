@extends('admin.layouts.app')
@section('title', 'Discussion Review')
@section('content')
<div class="mb-3"><a class="text-decoration-none" href="{{ route('admin.community.index') }}">← Back to Community Posts</a></div>
<div class="card border-0 mb-4">
  <div class="card-header bg-white p-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h1 class="h4 fw-bold mb-0">Discussion Review</h1>
    <form method="POST" action="{{ route('admin.community.destroy', $post) }}" onsubmit="return confirm('Remove this post AND ALL of its replies?');">
      @csrf @method('DELETE')
      <button type="submit" class="btn btn-outline-danger">Remove Post</button>
    </form>
  </div>
  <div class="card-body p-4">
    <div class="fw-semibold">{{ $post->author?->name ?? 'Former Member' }}</div>
    <div class="small text-muted mb-3">{{ $post->created_at->format('d M Y, h:i A') }}</div>
    <p class="mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $post->body }}</p>
  </div>
</div>
<div class="card border-0">
  <div class="card-header bg-white p-4">
    <h2 class="h5 fw-bold mb-0">Replies ({{ $post->replies_count }})</h2>
  </div>
  <div class="card-body p-0">
    @forelse($replies as $reply)
      <div class="p-4 border-bottom d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="flex-grow-1" style="min-width:170px">
          <div class="fw-semibold">{{ $reply->author?->name ?? 'Former Member' }}</div>
          <div class="small text-muted mb-2">{{ $reply->created_at->format('d M Y, h:i A') }}</div>
          <div style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $reply->body }}</div>
        </div>
        <form method="POST" action="{{ route('admin.community.replies.destroy', [$post, $reply, 'page' => $replies->currentPage()]) }}"
              onsubmit="return confirm('Remove only this reply?');">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm">Remove Reply</button>
        </form>
      </div>
    @empty
      <p class="text-muted p-4 mb-0">No replies yet.</p>
    @endforelse
  </div>
  @if($replies->hasPages()) <div class="card-footer bg-white p-3">{{ $replies->links() }}</div> @endif
</div>
@endsection
