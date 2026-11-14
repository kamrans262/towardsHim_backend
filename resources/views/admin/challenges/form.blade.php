@extends('admin.layouts.app')
@section('title', $challenge->exists ? 'Edit Challenge' : 'Create Challenge')
@section('content')
<a class="d-inline-block mb-3 text-decoration-none" href="{{ route('admin.challenges.index') }}">← Back to Challenges</a>
<div class="card border-0" style="max-width:920px">
 <div class="card-header bg-white p-4">
  <h1 class="h4 fw-bold mb-1">{{ $challenge->exists ? 'Edit Challenge' : 'Create Challenge' }}</h1>
  <p class="text-muted mb-0">Each challenge has exactly seven daily activities. Editing text does not reset member progress.</p>
 </div>
 <div class="card-body p-4">
  @if($errors->any())
   <div class="alert alert-danger" role="alert"><strong>Please correct the following:</strong>
    <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
   </div>
  @endif
  <form method="POST" action="{{ $challenge->exists ? route('admin.challenges.update', $challenge) : route('admin.challenges.store') }}">
   @csrf @if($challenge->exists) @method('PATCH') @endif
   <div class="mb-3">
    <label for="title" class="form-label fw-semibold">Challenge Title</label>
    <input id="title" name="title" class="form-control" required maxlength="180" value="{{ old('title', $challenge->title) }}">
   </div>
   <div class="mb-4">
    <label for="instructions" class="form-label fw-semibold">Instructions</label>
    <textarea id="instructions" name="instructions" class="form-control" rows="5" required maxlength="10000">{{ old('instructions', $challenge->instructions) }}</textarea>
   </div>
   <h2 class="h5 fw-semibold mb-3">Daily Activities</h2>
   @for($day = 1; $day <= 7; $day++)
    <div class="mb-3">
     <label class="form-label fw-semibold" for="day_{{ $day }}">Day {{ $day }}</label>
     <textarea id="day_{{ $day }}" name="days[{{ $day }}]" class="form-control" rows="2" required maxlength="5000">{{ old("days.$day", $dayTexts[$day] ?? '') }}</textarea>
    </div>
   @endfor
   @if($challenge->published_at)
    <div class="small text-muted mb-3">Published on {{ $challenge->published_at->format('d M Y') }}. Updates retain all existing completion records.</div>
   @endif
   <div class="d-flex flex-wrap gap-2 mt-4">
    @if(!$challenge->published_at)
     <button class="btn btn-outline-primary" name="action" value="draft" type="submit">Save Draft</button>
     <button class="btn btn-primary" name="action" value="publish" type="submit" onclick="return confirm('Publish this challenge to all members?');">Publish Challenge</button>
    @else
     <button class="btn btn-primary" name="action" value="publish" type="submit">Save Changes</button>
    @endif
    <a class="btn btn-outline-secondary" href="{{ route('admin.challenges.index') }}">Cancel</a>
   </div>
  </form>
 </div>
</div>
@endsection
