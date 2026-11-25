@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
    <h1 class="page-title">Categories</h1>

    <div class="post-layout">
        <div class="card side-box">
            <h3>Add new category</h3>
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="name">Name *</label>
                    <input class="form-input" type="text" id="name" name="name" maxlength="60" value="{{ old('name') }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="slug">Address (slug)</label>
                    <input class="form-input" type="text" id="slug" name="slug" maxlength="80" value="{{ old('slug') }}">
                    <div class="form-hint">Optional. Made from the name if empty.</div>
                    @error('slug') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <input class="form-input" type="text" id="description" name="description" maxlength="255" value="{{ old('description') }}">
                    @error('description') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn">Add new category</button>
            </form>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Name</th><th>Description</th><th>Address</th><th>Posts</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td><strong>{{ $category->name }}</strong></td>
                                <td class="text-muted">{{ $category->description ?? '-' }}</td>
                                <td class="text-muted">{{ $category->slug }}</td>
                                <td>{{ $category->posts_count }}</td>
                                <td>
                                    <div class="actions-row">
                                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-small btn-light">Edit</a>
                                        @unless ($category->isDefault())
                                            <form method="POST" action="{{ route('categories.destroy', $category) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this category? Its posts keep existing.');">Delete</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-muted" style="margin-top: 1rem;">Deleting a category never deletes its posts. A post left without any category moves to Uncategorized.</p>
        </div>
    </div>
@endsection
