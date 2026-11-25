{{-- A picture upload with preview and "remove". Expects: $field, $label, $url (current picture or null), $remove (name of the remove checkbox) --}}
<div class="form-group">
    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
    @if ($url)
        <img src="{{ $url }}" alt="" style="display: block; max-width: 220px; max-height: 120px; margin-bottom: 0.5rem; border-radius: 8px; border: 1px solid var(--color-border);">
    @endif
    <input class="form-input" type="file" id="{{ $field }}" name="{{ $field }}" accept="image/png,image/jpeg,image/webp">
    <div class="form-hint">PNG, JPG or WebP, up to 5 MB.</div>
    @if ($url)
        <label class="form-check"><input type="checkbox" name="{{ $remove }}" value="1"><span>Remove current picture</span></label>
    @endif
    @error($field) <div class="form-error">{{ $message }}</div> @enderror
</div>
