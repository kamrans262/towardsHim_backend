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
     @include('admin.partials.file_upload', [
       'id' => 'audioUpload',
       'field' => 'audio',
       'label' => 'Audio File (optional)',
       'path' => $item->audio_path,
       'originalName' => $item->audio_original_name,
       'url' => $item->audio_url,
       'removeField' => 'remove_audio',
       'accept' => '.mp3,.m4a,.aac,.ogg,.wav',
       'help' => 'MP3, M4A, AAC, OGG or WAV; up to 100 MB.',
     ])
    </div>
    <div class="col-md-6">
     @include('admin.partials.file_upload', [
       'id' => 'videoUpload',
       'field' => 'video',
       'label' => 'Video File (optional)',
       'path' => $item->video_path,
       'originalName' => $item->video_original_name,
       'url' => $item->video_url,
       'removeField' => 'remove_video',
       'accept' => '.mp4,.mov,.webm,.m4v',
       'help' => 'MP4, MOV, WEBM or M4V; up to 150 MB.',
     ])
    </div>
    <div class="col-md-6">
     @include('admin.partials.file_upload', [
       'id' => 'artworkUpload',
       'field' => 'artwork',
       'label' => 'Artwork (optional)',
       'path' => $item->artwork_path,
       'originalName' => $item->artwork_original_name,
       'url' => $item->artwork_url,
       'removeField' => 'remove_artwork',
       'accept' => '.jpg,.jpeg,.png,.webp',
       'help' => 'JPG, PNG or WEBP; maximum 5 MB.',
     ])
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
