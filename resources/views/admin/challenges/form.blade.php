@extends('admin.layouts.app')
@section('title', $challenge->exists ? 'Edit Challenge' : 'Create Challenge')
@section('content')
<a class="d-inline-block mb-3 text-decoration-none" href="{{ route('admin.challenges.index') }}">← Back to Challenges</a>
<div class="card border-0" style="max-width:920px">
 <div class="card-header bg-white p-4">
  <h1 class="h4 fw-bold mb-1">{{ $challenge->exists ? 'Edit Challenge' : 'Create Challenge' }}</h1>
  <p class="text-muted mb-0">Set up the week and its seven daily activities.</p>
 </div>
 <div class="card-body p-4">
  @if($errors->any())
   <div class="alert alert-danger" role="alert"><strong>Please correct the following:</strong>
    <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
   </div>
  @endif
  <form method="POST" enctype="multipart/form-data" action="{{ $challenge->exists ? route('admin.challenges.update', $challenge) : route('admin.challenges.store') }}">
   @csrf @if($challenge->exists) @method('PATCH') @endif
   <div class="mb-3">
    <label for="title" class="form-label fw-semibold">Challenge Title</label>
    <input id="title" name="title" class="form-control" required maxlength="180" value="{{ old('title', $challenge->title) }}">
   </div>
   <div class="mb-4">
    <label for="instructions" class="form-label fw-semibold">Instructions</label>
    <textarea id="instructions" name="instructions" class="form-control" rows="5" maxlength="10000">{{ old('instructions', $challenge->instructions) }}</textarea>
   </div>
   @include('admin.partials.simple_media_upload', ['owner' => $challenge, 'prefix' => 'media', 'heading' => 'Weekly Introduction Media (optional)'])
   <section class="admin-challenge-days" aria-labelledby="challenge-days-title">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
     <h2 class="h5 fw-semibold mb-0" id="challenge-days-title">Daily Activities</h2>
     <span class="small text-muted">7 days</span>
    </div>
    @for($day = 1; $day <= 7; $day++)
     @php
      $dayText = old("days.$day", $dayTexts[$day] ?? '');
      $dayRecord = $dayRecords[$day] ?? new \App\Models\WeeklyChallengeDay();
      $dayHasMedia = $dayRecord->exists && $dayRecord->media->isNotEmpty();
      $dayHasErrors = $errors->has("days.$day") || collect($errors->keys())->contains(
          fn ($key) => str_starts_with($key, "day_media.$day.")
      );
     @endphp
     <details class="admin-day-panel" id="challenge-day-{{ $day }}" data-day="{{ $day }}" @if($day === 1 || $dayHasErrors) open @endif>
      <summary class="admin-day-summary">
       <span class="admin-day-heading">
        <span class="admin-day-number">Day {{ $day }}</span>
        <span class="admin-day-preview" data-day-preview>{{ \Illuminate\Support\Str::limit(trim((string) $dayText), 68) ?: 'Add instructions or media' }}</span>
       </span>
       <span class="admin-day-meta">
        @if($dayHasMedia)<span class="admin-day-media-count">{{ $dayRecord->media->count() }} {{ \Illuminate\Support\Str::plural('file', $dayRecord->media->count()) }}</span>@endif
        <span class="admin-day-chevron" aria-hidden="true"></span>
       </span>
      </summary>
      <div class="admin-day-body">
       <div class="admin-day-instructions">
        <label class="form-label fw-semibold" for="day_{{ $day }}">Instructions</label>
        <textarea id="day_{{ $day }}" name="days[{{ $day }}]" class="form-control" rows="3" maxlength="5000">{{ $dayText }}</textarea>
       </div>
       @include('admin.partials.simple_media_upload', [
         'owner' => $dayRecord,
         'prefix' => "day_media[$day]",
         'heading' => 'Media',
         'compact' => true,
       ])
      </div>
     </details>
    @endfor
   </section>
   @once
   <script>
   document.addEventListener('DOMContentLoaded', function () {
     document.querySelectorAll('.admin-day-panel').forEach(function (panel) {
       var field = panel.querySelector('textarea[name^="days["]');
       var preview = panel.querySelector('[data-day-preview]');
       if (!field || !preview) return;
       field.addEventListener('input', function () {
         var value = field.value.trim().replace(/\s+/g, ' ');
         preview.textContent = value ? value.substring(0, 68) + (value.length > 68 ? '…' : '') : 'Add instructions or media';
       });
     });
   });
   </script>
   @endonce
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
