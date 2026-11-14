@extends('admin.layouts.app')
@section('title', 'Towards HIM 3')
@section('content')
<div class="card border-0">
 <div class="card-header bg-white p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
  <div><h1 class="h4 fw-bold mb-1">Weekly Challenges</h1><p class="text-muted mb-0">Publish seven daily activities for your members.</p></div>
  <a href="{{ route('admin.challenges.create') }}" class="btn btn-primary">Create Challenge</a>
 </div>
 <div class="card-body p-4">
  @if($current)
   <h2 class="h5 fw-bold mb-3">Current Weekly Challenge</h2>
   <div class="border rounded-3 p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div><strong>{{ $current->title }}</strong><div class="small text-muted">Published {{ $current->published_at->format('d M Y') }}</div></div>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.challenges.edit', $current) }}">Edit</a>
   </div>
  @else
   <div class="text-muted mb-4">No challenge published yet.</div>
  @endif

  <h2 class="h5 fw-bold mb-3">Previous Challenges</h2>
  @forelse($previous as $challenge)
   <div class="border-bottom py-3 d-flex align-items-center justify-content-between gap-2">
    <div><strong>{{ $challenge->title }}</strong><div class="small text-muted">{{ $challenge->published_at->format('d M Y') }}</div></div>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.challenges.edit', $challenge) }}">Edit</a>
   </div>
  @empty
   <p class="text-muted mb-4">No previous challenges yet.</p>
  @endforelse

  @if($drafts->isNotEmpty())
   <h2 class="h5 fw-bold mt-4 mb-3">Draft Challenges</h2>
   @foreach($drafts as $draft)
    <div class="border-bottom py-3 d-flex align-items-center justify-content-between gap-2">
     <div><strong>{{ $draft->title }}</strong><div class="small text-muted">Draft</div></div>
     <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.challenges.edit', $draft) }}">Edit / Publish</a>
    </div>
   @endforeach
  @endif
 </div>
</div>
@endsection
