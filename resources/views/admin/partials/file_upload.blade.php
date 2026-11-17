@php
    $uploadPath = $path ?? null;
    $uploadName = $originalName ?? null;
    $uploadLabel = $label ?? 'Upload file';
    $uploadId = $id ?? $field;
@endphp
<div class="admin-upload-block" data-upload-block>
 <label class="form-label fw-semibold" for="{{ $uploadId }}">{{ $uploadLabel }}</label>
 <div class="admin-upload-frame">
  @if($uploadPath)
   <div class="admin-upload-current" data-upload-current>
    <span class="admin-upload-current-name" title="{{ $uploadName ?: basename($uploadPath) }}">
     <span aria-hidden="true">📎</span> {{ $uploadName ?: basename($uploadPath) }}
    </span>
    <button type="button" class="admin-upload-remove" data-upload-remove aria-label="Remove {{ $uploadLabel }}" title="Remove current file">×</button>
   </div>
  @endif
  @if(!empty($removeField))
   <input type="hidden" data-upload-remove-value name="{{ $removeField }}" value="0">
  @endif
  <div class="admin-upload-new">
   <label class="admin-upload-choose" for="{{ $uploadId }}">Choose file</label>
   <span class="admin-upload-new-name" data-upload-new-name>{{ $uploadPath ? 'Choose a replacement' : 'No file selected' }}</span>
   <button type="button" class="admin-upload-clear d-none" data-upload-clear aria-label="Clear selected file" title="Clear selected file">×</button>
   <input id="{{ $uploadId }}" type="file" class="admin-upload-native" name="{{ $field }}" accept="{{ $accept ?? '' }}" aria-label="{{ $uploadLabel }}" style="position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;overflow:hidden!important;clip-path:inset(50%)!important;" data-upload-file>
  </div>
  @if($uploadPath)
   <button type="button" class="admin-upload-undo d-none" data-upload-undo>Undo removal</button>
  @endif
 </div>
 @if(!empty($help))
  <div class="form-text">{{ $help }}</div>
 @endif
 @if($uploadPath && !empty($url))
  <a class="d-inline-block mt-2 small" target="_blank" rel="noopener noreferrer" href="{{ $url }}">View current file</a>
 @endif
</div>
@once
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-upload-block]').forEach(function (block) {
    var input = block.querySelector('[data-upload-file]');
    var selected = block.querySelector('[data-upload-new-name]');
    var clear = block.querySelector('[data-upload-clear]');
    var current = block.querySelector('[data-upload-current]');
    var removeValue = block.querySelector('[data-upload-remove-value]');
    var undo = block.querySelector('[data-upload-undo]');
    var initial = current ? 'Choose a replacement' : 'No file selected';

    function setSelected() {
      var file = input && input.files && input.files[0];
      if (selected) selected.textContent = file ? file.name : initial;
      if (clear) clear.classList.toggle('d-none', !file);
    }

    if (input) input.addEventListener('change', setSelected);
    if (clear) clear.addEventListener('click', function () {
      input.value = '';
      setSelected();
    });

    var removeButton = block.querySelector('[data-upload-remove]');
    if (removeButton && removeValue) removeButton.addEventListener('click', function () {
      removeValue.value = '1';
      current.classList.add('d-none');
      if (undo) undo.classList.remove('d-none');
      initial = 'No file selected';
      setSelected();
    });
    if (undo && removeValue) undo.addEventListener('click', function () {
      removeValue.value = '0';
      if (current) current.classList.remove('d-none');
      undo.classList.add('d-none');
      initial = 'Choose a replacement';
      setSelected();
    });
  });
});
</script>
@endonce
