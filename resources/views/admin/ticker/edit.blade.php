@extends('layouts.admin')

@section('title', 'Edit headline')

@section('content')
    <h1 class="page-title">Edit headline</h1>

    <div class="card form-card">
        <form method="POST" action="{{ route('ticker.update', $item) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label" for="text">Headline *</label>
                <input class="form-input" type="text" id="text" name="text" maxlength="160" value="{{ old('text', $item->text) }}" required>
                @error('text') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="url">Link (optional)</label>
                <input class="form-input" type="text" id="url" name="url" maxlength="255" value="{{ old('url', $item->url) }}">
                @error('url') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="sort_order">Order</label>
                <input class="form-input" type="number" id="sort_order" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $item->sort_order) }}">
                @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <label class="form-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))><span>Show on the website</span></label>

            <div class="form-actions">
                <button type="submit" class="btn">Save</button>
                <a href="{{ route('ticker.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection
