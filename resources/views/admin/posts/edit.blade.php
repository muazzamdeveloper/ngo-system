@extends('layouts.admin')

@section('title', $post->exists ? 'Edit post' : 'Add new post')

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ $post->exists ? 'Edit post' : 'Add new post' }}</h1>
        <a href="{{ route('posts.index') }}" class="btn btn-light">All posts</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">Please fix the highlighted fields and try again. A picture must be chosen again.</div>
    @endif

    <form method="POST" action="{{ $post->exists ? route('posts.update', $post) : route('posts.store') }}" enctype="multipart/form-data" id="post-form">
        @csrf
        @if ($post->exists) @method('PUT') @endif

        <div class="post-layout">
            <div class="post-main">
                <div class="card">
                    <div class="form-group">
                        <label class="form-label" for="title">Title *</label>
                        <input class="form-input post-title-input" type="text" id="title" name="title" maxlength="200"
                               placeholder="Enter title here" value="{{ old('title', $post->title) }}" required>
                        @error('title') <div class="form-error">{{ $message }}</div> @enderror
                    </div>

                    @if ($post->exists)
                        <div class="form-group">
                            <label class="form-label" for="slug">Address (slug)</label>
                            <input class="form-input" type="text" id="slug" name="slug" maxlength="200" value="{{ old('slug', $post->slug) }}">
                            @error('slug') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label">Description</label>

                        <div class="classic-editor" data-classic-editor>
                            <div class="ce-tabs">
                                <button type="button" data-tab="visual" class="is-active">Visual</button>
                                <button type="button" data-tab="text">Text</button>
                            </div>
                            <div class="ce-toolbar">
                                <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                                <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                                <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                                <button type="button" data-cmd="strikeThrough" title="Strikethrough"><s>S</s></button>
                                <span class="ce-sep"></span>
                                <select data-block title="Format">
                                    <option value="p">Paragraph</option>
                                    <option value="h2">Heading 2</option>
                                    <option value="h3">Heading 3</option>
                                    <option value="h4">Heading 4</option>
                                    <option value="blockquote">Quote</option>
                                </select>
                                <span class="ce-sep"></span>
                                <button type="button" data-cmd="insertUnorderedList" title="Bulleted list">&bull; List</button>
                                <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
                                <span class="ce-sep"></span>
                                <button type="button" data-link title="Insert link">Link</button>
                                <button type="button" data-cmd="unlink" title="Remove link">Unlink</button>
                                <button type="button" data-cmd="insertHorizontalRule" title="Horizontal line">&mdash;</button>
                                <span class="ce-sep"></span>
                                <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
                                <button type="button" data-cmd="undo" title="Undo">&#8630;</button>
                                <button type="button" data-cmd="redo" title="Redo">&#8631;</button>
                            </div>
                            <div class="ce-visual" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Description"></div>
                            <textarea class="ce-text" name="content" rows="16">{{ \App\Support\HtmlCleaner::clean(old('content', $post->content)) }}</textarea>
                        </div>
                        @error('content') <div class="form-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="excerpt">Excerpt (optional)</label>
                        <textarea class="form-input" id="excerpt" name="excerpt" rows="3" maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea>
                        <div class="form-hint">A short summary shown on cards. If empty, the beginning of the description is used.</div>
                        @error('excerpt') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <aside class="post-side">
                <div class="card side-box">
                    <h3>Publish</h3>
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-input" id="status" name="status">
                            <option value="draft" @selected(old('status', $post->status) === 'draft')>Draft</option>
                            <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                        </select>
                        @error('status') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    @if ($post->exists)
                        <p class="text-muted">
                            By {{ $post->author?->name ?? 'unknown' }}.
                            @if ($post->published_at) Published {{ $post->published_at->format('d M Y, H:i') }}. @endif
                        </p>
                    @endif
                    <button type="submit" class="btn btn-block">{{ $post->exists ? 'Update' : 'Save' }}</button>
                </div>

                <div class="card side-box">
                    <h3>Categories</h3>
                    <div class="cat-list">
                        @foreach ($categories as $category)
                            <label class="form-check">
                                <input type="checkbox" name="categories[]" value="{{ $category->id }}" @checked(in_array($category->id, array_map('intval', old('categories', $selected)), true))>
                                <span>{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('categories.*') <div class="form-error">{{ $message }}</div> @enderror

                    @if ($canAddCategories)
                        <div class="form-group" style="margin-top: 0.8rem;">
                            <label class="form-label" for="new_categories">+ Add new category</label>
                            <input class="form-input" type="text" id="new_categories" name="new_categories" maxlength="400"
                                   placeholder="Separate several with commas" value="{{ old('new_categories') }}">
                            @error('new_categories') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <p class="form-hint">If none is ticked the post goes to Uncategorized.</p>
                    @endif
                </div>

                <div class="card side-box">
                    <h3>Featured image</h3>
                    @if ($post->imageUrl())
                        <img src="{{ $post->imageUrl() }}" alt="" style="display: block; width: 100%; border-radius: 8px; margin-bottom: 0.6rem;">
                    @endif
                    <input class="form-input" type="file" name="featured_image" accept="image/png,image/jpeg,image/webp">
                    <div class="form-hint">PNG, JPG or WebP, up to 5 MB.</div>
                    @if ($post->imageUrl())
                        <label class="form-check"><input type="checkbox" name="remove_featured_image" value="1"><span>Remove featured image</span></label>
                    @endif
                    @error('featured_image') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </aside>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/classic-editor.js') }}"></script>
@endpush
