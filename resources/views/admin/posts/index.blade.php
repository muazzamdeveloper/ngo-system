@extends('layouts.admin')

@section('title', 'Posts')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Posts</h1>
        <a href="{{ route('posts.create') }}" class="btn">+ Add new post</a>
    </div>

    <div class="pills">
        <a href="{{ route('posts.index', array_filter(['q' => $search, 'category' => $categoryId])) }}" class="pill {{ $status === null ? 'active' : '' }}">All <span class="pill-count">{{ $counts['all'] }}</span></a>
        <a href="{{ route('posts.index', array_filter(['status' => 'published', 'q' => $search, 'category' => $categoryId])) }}" class="pill {{ $status === 'published' ? 'active' : '' }}">Published <span class="pill-count">{{ $counts['published'] }}</span></a>
        <a href="{{ route('posts.index', array_filter(['status' => 'draft', 'q' => $search, 'category' => $categoryId])) }}" class="pill {{ $status === 'draft' ? 'active' : '' }}">Drafts <span class="pill-count">{{ $counts['draft'] }}</span></a>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('posts.index') }}" class="filters">
            @if ($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
            <input class="form-input" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search post titles">
            <select class="form-input" name="category" style="flex: 0 1 220px;">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn">Filter</button>
            @if ($search !== '' || $status || $categoryId) <a href="{{ route('posts.index') }}" class="btn btn-light">Clear</a> @endif
        </form>

        @if ($posts->isEmpty())
            <p class="empty-state">No posts found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th></th><th>Title</th><th>Author</th><th>Categories</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr>
                                <td>@if ($post->imageUrl())<img src="{{ $post->imageUrl() }}" alt="" style="width: 60px; height: 42px; object-fit: cover; border-radius: 6px;">@endif</td>
                                <td><a href="{{ route('posts.edit', $post) }}"><strong>{{ $post->title }}</strong></a></td>
                                <td>{{ $post->author?->name ?? '-' }}</td>
                                <td class="text-muted">{{ $post->categories->pluck('name')->join(', ') }}</td>
                                <td><span class="badge {{ $post->isPublished() ? 'badge-approved' : 'badge-pending' }}">{{ $post->isPublished() ? 'Published' : 'Draft' }}</span></td>
                                <td>{{ ($post->published_at ?? $post->created_at)->format('d M Y') }}</td>
                                <td>
                                    <div class="actions-row">
                                        <a href="{{ route('posts.edit', $post) }}" class="btn btn-small btn-light">Edit</a>
                                        <form method="POST" action="{{ route('posts.destroy', $post) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this post?');">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span class="text-muted">Showing {{ $posts->firstItem() }} to {{ $posts->lastItem() }} of {{ $posts->total() }}</span>
                {{ $posts->links() }}
            </div>
        @endif
    </div>
@endsection
