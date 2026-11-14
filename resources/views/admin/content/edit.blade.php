@extends('admin.layouts.app')
@section('title', 'Welcome & About Content')
@section('content')
<div class="card border-0" style="max-width:920px">
 <div class="card-header bg-white p-4">
  <h1 class="h4 fw-bold mb-1">Welcome &amp; About Content</h1>
  <p class="text-muted mb-0">These texts appear in the existing mobile Welcome and About screens. Onboarding text remains fixed.</p>
 </div>
 <div class="card-body p-4">
  @if($errors->any())
   <div class="alert alert-danger" role="alert">
    <strong>Please review the following:</strong>
    <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
   </div>
  @endif
  <form method="POST" action="{{ route('admin.content.update') }}">
   @csrf @method('PATCH')
   <div class="mb-3">
    <label class="form-label fw-semibold" for="welcome_heading">Welcome Heading</label>
    <input class="form-control" id="welcome_heading" name="welcome_heading" required maxlength="180" value="{{ old('welcome_heading', $content['welcome_heading']) }}">
   </div>
   <div class="mb-3">
    <label class="form-label fw-semibold" for="introduction_text">Introduction Text</label>
    <textarea class="form-control" id="introduction_text" name="introduction_text" rows="4" required maxlength="2000">{{ old('introduction_text', $content['introduction_text']) }}</textarea>
   </div>
   <div class="mb-4">
    <label class="form-label fw-semibold" for="about_information">About Information</label>
    <textarea class="form-control" id="about_information" name="about_information" rows="7" required maxlength="10000">{{ old('about_information', $content['about_information']) }}</textarea>
   </div>
   <button class="btn btn-primary px-4" type="submit">Save Changes</button>
  </form>
 </div>
</div>
@endsection
