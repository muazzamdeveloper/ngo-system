@extends('layouts.admin')

@section('title', 'Pages')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Pages</h1>
        <a href="{{ route('pages.create') }}" class="btn">+ New page</a>
    </div>
    <p class="text-muted">Pages such as About us, Terms or Contact. After creating a page, add its address (like /page/about-us) to a menu under "Menus &amp; footer links".</p>

    <div class="card">
        @if ($pages->isEmpty())
            <p class="empty-state">No pages yet.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Title</th><th>Address</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr>
                                <td><strong>{{ $page->title }}</strong></td>
                                <td class="text-muted">/page/{{ $page->slug }}</td>
                                <td><span class="badge {{ $page->is_published ? 'badge-approved' : 'badge-deactivated' }}">{{ $page->is_published ? 'Published' : 'Draft' }}</span></td>
                                <td>
                                    <div class="actions-row">
                                        @if ($page->is_published)<a href="{{ $page->url() }}" class="btn btn-small btn-light" target="_blank" rel="noopener">View</a>@endif
                                        <a href="{{ route('pages.edit', $page) }}" class="btn btn-small btn-light">Edit</a>
                                        <form method="POST" action="{{ route('pages.destroy', $page) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this page?');">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
