@extends('layouts.admin')

@section('title', $def['title'])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $def['title'] }}</h1>
            <p class="page-subtitle">{{ $def['subtitle'] ?? '' }}</p>
        </div>
        <a href="{{ route('modules.create', $key) }}" class="btn">+ Add {{ $def['singular'] }}</a>
    </div>

    <div class="pills">
        <a href="{{ route('modules.index', array_filter(['module' => $key, 'q' => $search])) }}" class="pill {{ $status === null ? 'active' : '' }}">All <span class="pill-count">{{ $counts['all'] }}</span></a>
        <a href="{{ route('modules.index', array_filter(['module' => $key, 'status' => 'active', 'q' => $search])) }}" class="pill {{ $status === 'active' ? 'active' : '' }}">Active <span class="pill-count">{{ $counts['active'] }}</span></a>
        <a href="{{ route('modules.index', array_filter(['module' => $key, 'status' => 'inactive', 'q' => $search])) }}" class="pill {{ $status === 'inactive' ? 'active' : '' }}">Inactive <span class="pill-count">{{ $counts['inactive'] }}</span></a>
    </div>

    <div class="card table-card">
        <form method="GET" action="{{ route('modules.index', $key) }}" class="toolbar">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input class="form-input toolbar-search" type="search" name="q" value="{{ $search }}" placeholder="Search {{ $def['label'] }}…" aria-label="Search">
            <button type="submit" class="btn btn-light">Search</button>
            @if ($search !== '' || $status)
                <a href="{{ route('modules.index', $key) }}" class="btn btn-light">Clear</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        @foreach ($def['columns'] as $col)
                            <th>{{ $col['label'] }}</th>
                        @endforeach
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            @foreach ($def['columns'] as $col)
                                <td>@include('admin.modules._cell', ['col' => $col, 'item' => $item])</td>
                            @endforeach
                            <td class="col-actions">
                                <div class="row-actions">
                                    <a href="{{ route('modules.edit', [$key, $item->id]) }}" class="btn btn-small btn-light">Edit</a>
                                    <form method="POST" action="{{ route('modules.toggle', [$key, $item->id]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-small btn-light">{{ $item->status === 'active' ? 'Hide' : 'Show' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('modules.destroy', [$key, $item->id]) }}" onsubmit="return confirm('Delete this {{ $def['singular'] }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($def['columns']) + 1 }}" class="empty-cell">
                                No {{ strtolower($def['label']) }} yet.
                                <a href="{{ route('modules.create', $key) }}">Add the first one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">{{ $items->links('vendor.pagination.custom') }}</div>
    </div>
@endsection
