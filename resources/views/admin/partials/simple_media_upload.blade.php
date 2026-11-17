@php
    $mediaBlocks = $owner->exists ? $owner->media : collect();
    $mediaPrefix = $prefix ?? 'media';
    $mediaId = 'media_upload_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $mediaPrefix);
@endphp
<div class="admin-simple-media mb-4" aria-label="{{ $heading ?? 'Media' }}">
 <div class="fw-semibold mb-2">{{ $heading ?? 'Media (optional)' }}</div>
 @foreach($mediaBlocks as $block)
  <div class="admin-media-existing" data-upload-block>
   @if($block->file_path)
    <div class="admin-upload-current" data-upload-current>
     <span class="admin-upload-current-name" title="{{ $block->original_name ?: basename($block->file_path) }}">
      <span class="small text-muted">{{ ucfirst($block->type) }}:</span>
      {{ $block->original_name ?: basename($block->file_path) }}
     </span>
     <button type="button" class="admin-upload-remove" data-upload-remove aria-label="Remove {{ $block->type }} file {{ $block->original_name ?: basename($block->file_path) }}" title="Remove saved file">×</button>
    </div>
   @else
    <div class="admin-upload-current" data-upload-current>
     <span class="admin-upload-current-name small text-muted">Previously added text (kept)</span>
     <button type="button" class="admin-upload-remove" data-upload-remove aria-label="Remove previous text content" title="Remove previous text">×</button>
    </div>
   @endif
   <input type="hidden" name="{{ $mediaPrefix }}[existing_{{ $block->id }}][id]" value="{{ $block->id }}">
   <input type="hidden" name="{{ $mediaPrefix }}[existing_{{ $block->id }}][type]" value="{{ $block->type }}">
   <input type="hidden" data-upload-remove-value name="{{ $mediaPrefix }}[existing_{{ $block->id }}][remove]" value="0">
   <button type="button" data-upload-undo class="admin-upload-undo d-none">Undo removal</button>
  </div>
 @endforeach
 <div class="row g-2 align-items-end">
  <div class="col-sm-4">
   <label class="form-label" for="{{ $mediaId }}_type">Media type</label>
   <select class="form-select" id="{{ $mediaId }}_type" name="{{ $mediaPrefix }}[upload][type]">
    <option value="image">Image</option>
    <option value="audio">Audio</option>
    <option value="video">Video</option>
   </select>
  </div>
  <div class="col-sm-8">
   @include('admin.partials.file_upload', [
     'id' => $mediaId . '_file',
     'field' => $mediaPrefix . '[upload][file]',
     'label' => 'Upload file',
     'path' => null,
     'accept' => '.jpg,.jpeg,.png,.webp,.mp3,.m4a,.aac,.ogg,.wav,.mp4,.mov,.webm,.m4v',
   ])
   <input type="hidden" name="{{ $mediaPrefix }}[upload][replace_type]" value="1">
  </div>
 </div>
 <div class="small text-muted mt-2">Choose an image, audio or video. Uploading the same type replaces the existing file; text instructions stay unchanged.</div>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.admin-simple-media').forEach(function (section) {
    var type = section.querySelector('select[name$="[upload][type]"]');
    var file = section.querySelector('input[data-upload-file]');
    if (!type || !file) return;
    function syncType() {
      var extensions = {
        image: '.jpg,.jpeg,.png,.webp',
        audio: '.mp3,.m4a,.aac,.ogg,.wav',
        video: '.mp4,.mov,.webm,.m4v'
      };
      file.accept = extensions[type.value] || '';
      file.value = '';
      file.dispatchEvent(new Event('change'));
    }
    type.addEventListener('change', syncType);
    syncType();
  });
});
</script>
@endonce
