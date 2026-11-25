@extends('layouts.admin')

@section('title', 'Volunteers')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Volunteers</h1>
            <p class="page-subtitle">Review people who signed up as volunteers on the website.</p>
        </div>
        <a href="{{ route('volunteer') }}" class="btn btn-light" target="_blank" rel="noopener">Open sign-up form</a>
    </div>

    <div class="pills">
        <a href="{{ route('volunteers.index', array_filter(['q' => $search])) }}" class="pill {{ $status === null ? 'active' : '' }}">All <span class="pill-count">{{ $counts['all'] }}</span></a>
        <a href="{{ route('volunteers.index', array_filter(['status' => 'pending', 'q' => $search])) }}" class="pill {{ $status === 'pending' ? 'active' : '' }}">Pending <span class="pill-count">{{ $counts['pending'] }}</span></a>
        <a href="{{ route('volunteers.index', array_filter(['status' => 'approved', 'q' => $search])) }}" class="pill {{ $status === 'approved' ? 'active' : '' }}">Approved <span class="pill-count">{{ $counts['approved'] }}</span></a>
        <a href="{{ route('volunteers.index', array_filter(['status' => 'rejected', 'q' => $search])) }}" class="pill {{ $status === 'rejected' ? 'active' : '' }}">Rejected <span class="pill-count">{{ $counts['rejected'] }}</span></a>
    </div>

    <div class="card table-card">
        <form method="GET" action="{{ route('volunteers.index') }}" class="toolbar">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input class="form-input toolbar-search" type="search" name="q" value="{{ $search }}" placeholder="Search name, email, mobile or city…" aria-label="Search volunteers">
            <button type="submit" class="btn btn-light">Search</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Volunteer</th><th>Contact</th><th>Location</th><th>Interests</th><th>Signed up</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
                <tbody>
                    @forelse ($volunteers as $v)
                        <tr>
                            <td><span class="strong">{{ $v->full_name }}</span>@if($v->message)<div class="muted small">{{ \Illuminate\Support\Str::limit($v->message, 80) }}</div>@endif</td>
                            <td class="small">{{ $v->mobile }}@if($v->email)<div>{{ $v->email }}</div>@endif</td>
                            <td class="small">{{ collect([$v->city, $v->state])->filter()->implode(', ') ?: '—' }}</td>
                            <td class="small">{{ $v->interests ?: '—' }}</td>
                            <td class="small">{{ $v->created_at?->format('d M Y') }}</td>
                            <td><span class="status-badge status-{{ $v->status }}">{{ \App\Models\Volunteer::STATUSES[$v->status] ?? $v->status }}</span></td>
                            <td class="col-actions">
                                <div class="row-actions">
                                    @if ($v->status !== 'approved')
                                        <form method="POST" action="{{ route('volunteers.review', $v) }}">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-small">Approve</button></form>
                                    @endif
                                    @if ($v->status !== 'rejected')
                                        <form method="POST" action="{{ route('volunteers.review', $v) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-small btn-light">Reject</button></form>
                                    @endif
                                    <form method="POST" action="{{ route('volunteers.destroy', $v) }}" onsubmit="return confirm('Remove this volunteer?');">@csrf @method('DELETE')<button class="btn btn-small btn-danger">Delete</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell">No volunteers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $volunteers->links('vendor.pagination.custom') }}</div>
    </div>
@endsection
