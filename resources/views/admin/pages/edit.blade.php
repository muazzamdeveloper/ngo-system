@extends('layouts.admin')

@section('title', $page->exists ? 'Edit page' : 'New page')

@section('content')
    <h1 class="page-title">{{ $page->exists ? 'Edit page' : 'New page' }}</h1>

    <div class="card">
        <form method="POST" action="{{ $page->exists ? route('pages.update', $page) : route('pages.store') }}" enctype="multipart/form-data">
            @csrf
            @if ($page->exists) @method('PUT') @endif

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="title">Title *</label>
                    <input class="form-input" type="text" id="title" name="title" maxlength="160" value="{{ old('title', $page->title) }}" required>
                    @error('title') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="slug">Address</label>
                    <input class="form-input" type="text" id="slug" name="slug" maxlength="100" placeholder="about-us" value="{{ old('slug', $page->slug) }}">
                    <div class="form-hint">The page opens at /page/<em>address</em>. Leave empty to make it from the title.</div>
                    @error('slug') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group form-grid-full">
                    <label class="form-label" for="summary">Short summary (for search engines)</label>
                    <input class="form-input" type="text" id="summary" name="summary" maxlength="300" value="{{ old('summary', $page->summary) }}">
                    @error('summary') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group form-grid-full">
                    <label class="form-label" for="body">Page text</label>
                    <div class="editor-bar" data-editor-for="body">
                        <button type="button" data-md="**|**" title="Bold"><b>B</b></button>
                        <button type="button" data-md="*|*" title="Italic"><i>I</i></button>
                        <button type="button" data-md-line="## " title="Heading">H</button>
                        <button type="button" data-md-line="- " title="List">&bull; List</button>
                        <button type="button" data-md="[|](https://)" title="Link">Link</button>
                    </div>
                    <textarea class="form-input" id="body" name="body" rows="16" maxlength="60000">{{ old('body', $page->body) }}</textarea>
                    <div class="form-hint">Blank line = new paragraph. Use the buttons above for bold, headings, lists and links. HTML code is not allowed.</div>
                    @error('body') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                @include('admin.partials.image-field', ['field' => 'image', 'label' => 'Top picture (optional)', 'url' => $page->imageUrl(), 'remove' => 'remove_image'])
            </div>

            <label class="form-check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))><span>Published (visible to everyone)</span></label>

            <div class="form-actions">
                <button type="submit" class="btn">Save page</button>
                <a href="{{ route('pages.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin-editor.js') }}"></script>
@endpush
