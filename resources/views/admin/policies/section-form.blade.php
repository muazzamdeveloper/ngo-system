@extends('layouts.admin')
@section('title', $section->exists ? 'Edit policy section' : 'Add policy section')
@section('content')
    @php $isEdit = $section->exists; @endphp
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $isEdit ? 'Edit section' : 'Add policy section' }}</h1>
            <p class="page-subtitle">{{ $policy->title }}</p>
        </div>
        <a href="{{ route('policies.show', $type) }}" class="btn btn-light">&larr; Back to policy</a>
    </div>
    <form method="POST" action="{{ $isEdit ? route('policies.sections.update', [$type, $section]) : route('policies.sections.store', $type) }}" class="card form-card">
        @csrf @if ($isEdit) @method('PUT') @endif
        <div class="form-grid">
            <div class="form-group span-2">
                <label class="form-label" for="title">Section title <span class="req">*</span></label>
                <input class="form-input" id="title" name="title" value="{{ old('title', $section->title) }}" maxlength="255" required>
                @error('title') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="icon">Icon (Font Awesome class)</label>
                <input class="form-input" id="icon" name="icon" value="{{ old('icon', $section->icon) }}" maxlength="60" placeholder="fa-shield-halved">
                <div class="form-hint">Example: fa-shield-halved, fa-lock, fa-user-shield</div>
                @error('icon') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="sort_order">Sort order <span class="req">*</span></label>
                <input class="form-input" id="sort_order" type="number" min="0" max="9999" name="sort_order" value="{{ old('sort_order', $section->sort_order) }}" required>
                @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group span-2">
                <label class="form-label">Section content <span class="req">*</span></label>
                @include('admin.partials.rich-text', ['name' => 'content', 'value' => \App\Support\HtmlCleaner::clean(old('content', $section->content)), 'label' => 'Section content'])
                @error('content') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select class="form-input" id="status" name="status">
                    <option value="active" @selected(old('status', $section->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $section->status) === 'inactive')>Inactive</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn">Save section</button>
            <a href="{{ route('policies.show', $type) }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
@endsection
