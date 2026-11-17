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
        <span class="admin-day-chevron-wrap" aria-hidden="true">
         <svg class="admin-day-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" focusable="false">
          <path d="m6 9 6 6 6-6"/>
         </svg>
        </span>
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
   <style>

/* Challenge day accordion — included by the view so Hostinger's public CSS copy
   cannot silently fall back to browser-default details/summary styling. */
.toward-admin .admin-challenge-days { margin-top:36px!important; margin-bottom:30px!important; }
.toward-admin .admin-challenge-days .admin-day-panel {
  display:block!important; margin:0 0 16px!important; padding:0!important;
  border:1px solid #e8dcd1!important; border-radius:16px!important;
  background:#fff!important; overflow:hidden!important;
  box-shadow:0 3px 14px rgba(51,36,21,.045)!important;
}
.toward-admin .admin-challenge-days .admin-day-panel[open] {
  border-color:rgba(228,120,33,.42)!important;
  box-shadow:0 5px 18px rgba(51,36,21,.055)!important;
}
.toward-admin .admin-challenge-days .admin-day-summary {
  display:flex!important; align-items:center!important; justify-content:space-between!important;
  gap:20px!important; min-height:88px!important;
  padding:17px 22px!important; margin:0!important; width:100%!important;
  background:#fff!important; list-style:none!important; cursor:pointer!important;
  box-sizing:border-box!important;
}
.toward-admin .admin-challenge-days .admin-day-summary::-webkit-details-marker { display:none!important; }
.toward-admin .admin-challenge-days .admin-day-summary::marker { content:''!important; font-size:0!important; }
.toward-admin .admin-challenge-days .admin-day-summary:hover { background:#fffaf4!important; }
.toward-admin .admin-challenge-days .admin-day-summary:focus-visible { outline:2px solid #E47821!important; outline-offset:-3px!important; }
.toward-admin .admin-challenge-days .admin-day-panel[open]>.admin-day-summary {
  background:#fffbf6!important; border-bottom:1px solid #f0e6dc!important;
}
.toward-admin .admin-challenge-days .admin-day-heading {
  display:flex!important; flex-direction:column!important; align-items:flex-start!important;
  gap:9px!important; min-width:0!important; flex:1 1 auto!important;
}
.toward-admin .admin-challenge-days .admin-day-number {
  display:block!important; color:#22252d!important;
  font-size:16px!important; font-weight:700!important; line-height:1.3!important;
}
.toward-admin .admin-challenge-days .admin-day-preview {
  display:block!important; margin-top:1px!important; max-width:100%!important;
  color:#85838a!important; font-size:13px!important; line-height:1.45!important;
  font-weight:400!important; white-space:nowrap!important; overflow:hidden!important;
  text-overflow:ellipsis!important; text-align:left!important;
}
.toward-admin .admin-challenge-days .admin-day-meta {
  display:flex!important; align-items:center!important; justify-content:flex-end!important;
  flex-shrink:0!important; gap:14px!important;
}
.toward-admin .admin-challenge-days .admin-day-media-count {
  font-size:12px!important; font-weight:500!important; color:#E47821!important; white-space:nowrap!important;
}
.toward-admin .admin-challenge-days .admin-day-chevron-wrap {
  display:flex!important; width:36px!important; height:36px!important;
  align-items:center!important; justify-content:center!important;
  flex:0 0 36px!important; border-radius:50%!important;
  color:#E47821!important; background:#fff2e5!important;
}
.toward-admin .admin-challenge-days .admin-day-chevron {
  display:block!important; width:20px!important; height:20px!important;
  border:0!important; transform:rotate(0deg)!important;
  transition:transform .18s ease!important;
}
.toward-admin .admin-challenge-days .admin-day-panel[open] .admin-day-chevron {
  transform:rotate(180deg)!important;
}
.toward-admin .admin-challenge-days .admin-day-body {
  display:flex!important; flex-direction:column!important;
  gap:24px!important; padding:25px 22px 26px!important;
  background:#fff!important;
}
.toward-admin .admin-challenge-days .admin-day-instructions .form-label {
  display:block!important; margin-bottom:11px!important;
}
.toward-admin .admin-challenge-days .admin-day-instructions textarea {
  min-height:108px!important; border-radius:9px!important;
}
.toward-admin .admin-challenge-days .admin-day-body .admin-simple-media {
  margin:0!important; padding:0!important;
}
@media(max-width:575.98px) {
 .toward-admin .admin-challenge-days .admin-day-summary { padding:16px!important; min-height:82px!important; gap:10px!important; }
 .toward-admin .admin-challenge-days .admin-day-heading { gap:7px!important; }
 .toward-admin .admin-challenge-days .admin-day-preview { font-size:12px!important; }
 .toward-admin .admin-challenge-days .admin-day-body { padding:20px 16px 22px!important; }
 .toward-admin .admin-challenge-days .admin-day-chevron-wrap { width:32px!important; height:32px!important; flex-basis:32px!important; }
}

   </style>
   @endonce
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
