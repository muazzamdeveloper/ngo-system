{{-- Boxes for one item. Expects: $section, $item (HomeItem or null) --}}
@php($fields = $section->definition()['item'])
<div class="form-grid">
    @if (in_array('title', $fields, true))
        <div class="form-group">
            <label class="form-label" for="title">Title / heading</label>
            <input class="form-input" type="text" id="title" name="title" maxlength="255" value="{{ old('title', $item?->title) }}">
            @error('title') <div class="form-error">{{ $message }}</div> @enderror
        </div>
    @endif

    @if (in_array('subtitle', $fields, true))
        <div class="form-group">
            <label class="form-label" for="subtitle">{{ in_array($section->type, ['people', 'testimonials'], true) ? 'Role / place' : 'Small text' }}</label>
            <input class="form-input" type="text" id="subtitle" name="subtitle" maxlength="255" value="{{ old('subtitle', $item?->subtitle) }}">
            @error('subtitle') <div class="form-error">{{ $message }}</div> @enderror
        </div>
    @endif

    @if (in_array('rating', $fields, true))
        <div class="form-group">
            <label class="form-label" for="rating">Stars (1 to 5)</label>
            <input class="form-input" type="number" id="rating" name="rating" min="1" max="5" value="{{ old('rating', $item?->rating) }}">
            @error('rating') <div class="form-error">{{ $message }}</div> @enderror
        </div>
    @endif

    @if (in_array('body', $fields, true))
        <div class="form-group form-grid-full">
            <label class="form-label" for="body">Text</label>
            <textarea class="form-input" id="body" name="body" rows="4" maxlength="3000">{{ old('body', $item?->body) }}</textarea>
            @error('body') <div class="form-error">{{ $message }}</div> @enderror
        </div>
    @endif

    @if (in_array('image', $fields, true))
        @include('admin.partials.image-field', ['field' => 'image', 'label' => 'Picture', 'url' => $item?->imageUrl(), 'remove' => 'remove_image'])
    @endif

    @if (in_array('link', $fields, true))
        <div class="form-group">
            <label class="form-label" for="link_label">Button text</label>
            <input class="form-input" type="text" id="link_label" name="link_label" maxlength="60" value="{{ old('link_label', $item?->link_label) }}">
            @error('link_label') <div class="form-error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="link_url">Button link</label>
            <input class="form-input" type="text" id="link_url" name="link_url" maxlength="255" placeholder="/apply or https://..." value="{{ old('link_url', $item?->link_url) }}">
            @error('link_url') <div class="form-error">{{ $message }}</div> @enderror
        </div>
    @endif

    <div class="form-group">
        <label class="form-label" for="sort_order">Order (smaller comes first)</label>
        <input class="form-input" type="number" id="sort_order" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $item?->sort_order) }}">
        @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
    </div>
</div>
<label class="form-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item?->is_active ?? true))><span>Show on the website</span></label>
