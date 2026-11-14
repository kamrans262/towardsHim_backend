@extends('admin.layouts.app')
@section('title', 'Community Moderation')
@section('content')
<div class="card border-0">
  <div class="card-header bg-white p-4">
    <h1 class="h4 fw-bold mb-1">Community Posts</h1>
    <p class="text-muted mb-0">Review member discussions and replies.</p>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Member</th><th>Post</th><th>Replies</th><th>Posted</th><th>Action</th></tr></thead>
        <tbody>
          @forelse($posts as $post)
            <tr>
              <td class="fw-semibold">{{ $post->author?->name ?? 'Former Member' }}</td>
              <td style="max-width:320px">{{ Str::limit($post->body, 90) }}</td>
              <td>{{ $post->replies_count }}</td>
              <td>{{ $post->created_at->format('d M Y, h:i A') }}</td>
              <td><a class="btn btn-outline-primary btn-sm" href="{{ route('admin.community.show', $post) }}">Review</a></td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted p-4">No member posts yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($posts->hasPages()) <div class="card-footer bg-white p-3">{{ $posts->links() }}</div> @endif
</div>
@endsection
