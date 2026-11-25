@extends('layouts.admin')

@section('title', 'Members')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Members</h1>
        @can('members.create')
            <a href="{{ route('members.create') }}" class="btn">+ Add member</a>
        @endcan
    </div>

    {{-- Status filter pills (keep the search text when switching) --}}
    <div class="pills">
        <a href="{{ route('members.index', array_filter(['q' => $search])) }}"
           class="pill {{ $status === null ? 'active' : '' }}">
            All <span class="pill-count">{{ $totalCount }}</span>
        </a>
        @foreach (\App\Models\Member::STATUSES as $s)
            <a href="{{ route('members.index', array_filter(['q' => $search, 'status' => $s])) }}"
               class="pill {{ $status === $s ? 'active' : '' }}">
                {{ ucfirst($s) }} <span class="pill-count">{{ $counts[$s] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" action="{{ route('members.index') }}" class="filters">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif

            <input class="form-input" type="search" name="q" value="{{ $search }}" maxlength="100"
                   placeholder="Search name, father name, mobile, email or membership number">
            <button type="submit" class="btn">Search</button>

            @if ($search !== '' || $status)
                <a href="{{ route('members.index') }}" class="btn btn-light">Clear</a>
            @endif
        </form>

        @if ($members->isEmpty())
            <p class="empty-state">No members found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Membership no.</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>City</th>
                            <th>Aadhaar</th>
                            <th>Status</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $member)
                            <tr>
                                <td>{{ $member->membership_number ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('members.show', $member) }}"><strong>{{ $member->full_name }}</strong></a><br>
                                    <span class="text-muted">Father: {{ $member->father_name }}</span>
                                </td>
                                <td>{{ $member->mobile }}</td>
                                <td>{{ $member->city }}</td>
                                <td>{{ $member->maskedAadhaar() }}</td>
                                <td><span class="badge badge-{{ $member->status }}">{{ ucfirst($member->status) }}</span></td>
                                <td>{{ $member->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span class="text-muted">
                    Showing {{ $members->firstItem() }} to {{ $members->lastItem() }} of {{ $members->total() }}
                </span>
                {{ $members->links() }}
            </div>
        @endif
    </div>
@endsection
