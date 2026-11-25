@extends('layouts.admin')

@section('title', 'Edit category')

@section('content')
    <h1 class="page-title">Edit category</h1>

    <div class="card form-card">
        <form method="POST" action="{{ route('categories.update', $category) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label" for="name">Name *</label>
                <input class="form-input" type="text" id="name" name="name" maxlength="60" value="{{ old('name', $category->name) }}" required>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="slug">Address (slug)</label>
                <input class="form-input" type="text" id="slug" name="slug" maxlength="80" value="{{ old('slug', $category->slug) }}" @disabled($category->isDefault())>
                @if ($category->isDefault()) <div class="form-hint">The default category's address cannot be changed.</div> @endif
                @error('slug') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <input class="form-input" type="text" id="description" name="description" maxlength="255" value="{{ old('description', $category->description) }}">
                @error('description') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Save</button>
                <a href="{{ route('categories.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection
