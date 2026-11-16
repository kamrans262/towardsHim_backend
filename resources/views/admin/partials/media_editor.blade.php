@php
    $mediaBlocks = $owner->exists ? $owner->media : collect();
    $mediaPrefix = $prefix ?? 'media';
@endphp
<section class="border rounded-3 p-3 mb-4 media-editor" aria-label="Additional content">
 <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
  <div>
   <h3 class="h6 fw-bold mb-1">{{ $heading ?? 'Additional Content' }}</h3>
   <p class="small text-muted mb-0">Add multiple text, image, audio, or video blocks. Existing content is preserved unless you remove it.</p>
  </div>
  <button type="button" class="btn btn-outline-primary btn-sm media-add">Add Content</button>
 </div>
 <div class="media-rows">
  @foreach ($mediaBlocks as $block)
   <div class="border rounded-3 p-3 mt-3 media-row">
    <input type="hidden" name="{{ $mediaPrefix }}[existing_{{ $block->id }}][id]" value="{{ $block->id }}">
    <input type="hidden" name="{{ $mediaPrefix }}[existing_{{ $block->id }}][type]" value="{{ $block->type }}">
    <div class="fw-semibold mb-2">{{ ucfirst($block->type) }} #{{ $loop->iteration }}</div>
    @if ($block->file_path)
     <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($block->file_path) }}" target="_blank" rel="noopener noreferrer">View current file</a>
    @endif
    <label class="form-label mt-2" for="media_{{ $block->id }}_body">Text / caption</label>
    <textarea id="media_{{ $block->id }}_body" class="form-control mb-2" rows="2" maxlength="20000" name="{{ $mediaPrefix }}[existing_{{ $block->id }}][body]">{{ $block->body }}</textarea>
    @if($block->type !== 'text')
     <label class="form-label">Replace file (optional)</label>
     <input type="file" class="form-control mb-2" name="{{ $mediaPrefix }}[existing_{{ $block->id }}][file]">
    @endif
    <label class="form-check">
     <input class="form-check-input" type="checkbox" name="{{ $mediaPrefix }}[existing_{{ $block->id }}][remove]" value="1">
     <span class="form-check-label">Remove this content block</span>
    </label>
   </div>
  @endforeach
 </div>
 <template class="media-template">
  <div class="border rounded-3 p-3 mt-3 media-row">
   <div class="row g-2">
    <div class="col-12 col-md-4">
     <label class="form-label">Content type</label>
     <select class="form-select" name="{{ $mediaPrefix }}[__KEY__][type]">
      <option value="text">Text</option><option value="image">Image</option>
      <option value="audio">Audio</option><option value="video">Video</option>
     </select>
    </div>
    <div class="col-12 col-md-8">
     <label class="form-label">File (image, audio, or video)</label>
     <input class="form-control" type="file" name="{{ $mediaPrefix }}[__KEY__][file]">
    </div>
    <div class="col-12">
     <label class="form-label">Text / caption</label>
     <textarea class="form-control" rows="2" maxlength="20000" name="{{ $mediaPrefix }}[__KEY__][body]"></textarea>
    </div>
   </div>
   <button type="button" class="btn btn-outline-danger btn-sm mt-2 media-discard">Discard</button>
  </div>
 </template>
</section>
@once
<script>
document.addEventListener('DOMContentLoaded', function () {
  let nextMediaKey = 0;
  document.querySelectorAll('.media-editor').forEach(function (editor) {
    const add = editor.querySelector('.media-add');
    const rows = editor.querySelector('.media-rows');
    const template = editor.querySelector('.media-template');
    add.addEventListener('click', function () {
      const markup = template.innerHTML.replaceAll('__KEY__', 'new_' + (++nextMediaKey));
      rows.insertAdjacentHTML('beforeend', markup);
    });
    rows.addEventListener('click', function (event) {
      if (event.target.closest('.media-discard')) {
        event.target.closest('.media-row').remove();
      }
    });
  });
});
</script>
@endonce
