@extends('admin.layouts.app')
@section('title', $label)
@section('content')
<div class="card border-0">
 <div class="card-header bg-white p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div><h1 class="h4 fw-bold mb-1">Manage {{ $label }}</h1><p class="text-muted mb-0">Upload, edit and publish audio, video and written content for the mobile app.</p></div>
  <a class="btn btn-primary" href="{{ route('admin.audio.create', $category) }}">Add {{ Str::singular($label) }}</a>
 </div>
 <div class="card-body p-0"><div class="table-responsive">
  <table class="table align-middle mb-0">
   <thead><tr><th>Title</th><th>Duration</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead>
   <tbody>
    @forelse($items as $item)
     <tr>
      <td><div class="fw-semibold">{{ $item->title }}</div><div class="small text-muted">{{ Str::limit($item->description, 75) }}</div>
       @if($item->video_path)<div class="small text-muted">Video attached</div>@endif
       @if($item->media->isNotEmpty())<div class="small text-muted">{{ $item->media->pluck('type')->unique()->map(fn ($type) => ucfirst($type))->implode(' · ') }}</div>@endif
      </td>
      <td>{{ $item->duration_seconds ? ceil($item->duration_seconds / 60).' min' : '—' }}</td>
      <td><span class="badge {{ $item->is_published ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $item->is_published ? 'Published' : 'Draft' }}</span></td>
      <td>{{ $item->published_at?->format('d M Y') ?? '—' }}</td>
      <td><div class="admin-row-actions">
       <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.audio.edit', [$category, $item]) }}">Edit</a>
       <form method="POST" action="{{ route('admin.audio.destroy', [$category, $item]) }}" onsubmit="return confirm('Remove this audio and its uploaded files?');">
        @csrf @method('DELETE') <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
       </form>
      </div></td>
     </tr>
    @empty
     <tr><td colspan="5" class="text-center text-muted p-4">No {{ strtolower($label) }} available yet. Add an audio item to begin.</td></tr>
    @endforelse
   </tbody>
  </table>
 </div></div>
 @if($items->hasPages())<div class="card-footer bg-white">{{ $items->links() }}</div>@endif
</div>
@endsection
