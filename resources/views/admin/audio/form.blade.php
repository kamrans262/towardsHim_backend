@extends('admin.layouts.app')
@section('title', ($item->exists ? 'Edit ' : 'Add ').Str::singular($label))
@section('content')
<div class="mb-3"><a class="text-decoration-none" href="{{ route('admin.audio.index', $category) }}">← Back to {{ $label }}</a></div>
<div class="card border-0" style="max-width:900px">
 <div class="card-header bg-white p-4">
  <h1 class="h4 fw-bold mb-1">{{ $item->exists ? 'Edit' : 'Add' }} {{ Str::singular($label) }}</h1>
  <p class="text-muted mb-0">Published content appears in Listen for members and guests. Add audio, video or artwork alongside your description.</p>
 </div>
 <div class="card-body p-4">
  @if($errors->any())
   <div class="alert alert-danger" role="alert">
    <strong>Please correct the following:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
   </div>
  @endif
  <form method="POST" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.audio.update', [$category, $item]) : route('admin.audio.store', $category) }}">
   @csrf @if($item->exists) @method('PATCH') @endif
   <div class="mb-3">
    <label for="audioTitle" class="form-label fw-semibold">Title</label>
    <input id="audioTitle" name="title" class="form-control" type="text" maxlength="180" required value="{{ old('title', $item->title) }}">
   </div>
   <div class="mb-3">
    <label for="audioDescription" class="form-label fw-semibold">Description</label>
    <textarea id="audioDescription" name="description" rows="5" maxlength="10000" required class="form-control">{{ old('description', $item->description) }}</textarea>
   </div>
   <div class="row g-3 mb-4">
    <div class="col-md-6">
     <label for="audioUpload" class="form-label fw-semibold">Audio file {{ $item->audio_path ? '(replace existing)' : '' }}</label>
     <input id="audioUpload" name="audio" type="file" class="form-control" accept=".mp3,.m4a,.mp4,.aac,.ogg,.wav,audio/*">
     <div class="form-text">MP3, M4A, AAC, OGG or WAV, up to 100 MB.</div>
     @if($item->audio_path)<a class="d-inline-block mt-2" href="{{ $item->audio_url }}" target="_blank" rel="noopener noreferrer">Listen to uploaded audio</a>@endif
    </div>
    <div class="col-md-6">
     <label for="videoUpload" class="form-label fw-semibold">Video File (optional) {{ $item->video_path ? '(replace existing)' : '' }}</label>
     <input id="videoUpload" name="video" type="file" class="form-control" accept=".mp4,.mov,.webm,.m4v,video/*">
     <div class="form-text">MP4, MOV, WEBM or M4V, up to 150 MB.</div>
     @if($item->video_path)
      <div class="mt-2">
       <a href="{{ $item->video_url }}" target="_blank" rel="noopener noreferrer">View uploaded video</a>
       <label class="form-check mt-2">
        <input class="form-check-input" type="checkbox" name="remove_video" value="1">
        <span class="form-check-label">Remove current video</span>
       </label>
      </div>
     @endif
    </div>
    <div class="col-md-6">
     <label for="artworkUpload" class="form-label fw-semibold">Artwork (optional)</label>
     <input id="artworkUpload" name="artwork" type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
     <div class="form-text">JPG, PNG or WEBP, maximum 5 MB.</div>
     @if($item->artwork_path)
      <div class="mt-2">
       <img src="{{ $item->artwork_url }}" alt="Current cover" style="width:100px;height:100px;object-fit:cover;border-radius:10px">
       <label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_artwork" value="1"><span class="form-check-label">Remove current artwork</span></label>
      </div>
     @endif
    </div>
    <div class="col-md-6">
     <label for="duration" class="form-label fw-semibold">Duration in minutes (optional)</label>
     <input id="duration" name="duration_minutes" class="form-control" type="number" min="1" max="720" value="{{ old('duration_minutes', $item->duration_seconds ? ceil($item->duration_seconds / 60) : '') }}">
    </div>
    <div class="col-md-6">
     <label for="status" class="form-label fw-semibold">Visibility</label>
     <select id="status" name="status" class="form-select" required>
      <option value="draft" @selected(old('status', $item->is_published ? 'published' : 'draft') === 'draft')>Save as draft</option>
      <option value="published" @selected(old('status', $item->is_published ? 'published' : 'draft') === 'published')>Publish to app</option>
     </select>
    </div>
   </div>
   <div class="d-flex gap-2 flex-wrap mt-4">
    <button type="submit" class="btn btn-primary px-4">{{ $item->exists ? 'Save Changes' : 'Save Audio' }}</button>
    <a href="{{ route('admin.audio.index', $category) }}" class="btn btn-outline-secondary">Cancel</a>
   </div>
  </form>
 </div>
</div>
@endsection
