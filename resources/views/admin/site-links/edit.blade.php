@extends('layouts.admin')

@section('title', 'Edit link')

@section('content')
    <h1 class="page-title">Edit link</h1>

    <div class="card form-card">
        <form method="POST" action="{{ route('site-links.update', $link) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label" for="group">Where *</label>
                <select class="form-input" id="group" name="group" required>
                    @foreach (\App\Models\SiteLink::GROUPS as $key => $label)
                        <option value="{{ $key }}" @selected(old('group', $link->group) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('group') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="label">Text *</label>
                <input class="form-input" type="text" id="label" name="label" maxlength="60" value="{{ old('label', $link->label) }}" required>
                @error('label') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="url">Link *</label>
                <input class="form-input" type="text" id="url" name="url" maxlength="255" value="{{ old('url', $link->url) }}" required>
                @error('url') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="sort_order">Order</label>
                <input class="form-input" type="number" id="sort_order" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $link->sort_order) }}">
                @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <label class="form-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $link->is_active))><span>Show on the website</span></label>
            <label class="form-check"><input type="checkbox" name="new_tab" value="1" @checked(old('new_tab', $link->new_tab))><span>Open in a new tab</span></label>

            <div class="form-actions">
                <button type="submit" class="btn">Save</button>
                <a href="{{ route('site-links.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection
