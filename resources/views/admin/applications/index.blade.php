@extends('layouts.admin')

@section('title', 'Applications')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Applications</h1>
    </div>

    <p class="text-muted" style="margin-bottom: 1rem;">
        Applications sent through the public form. Open one to see its documents and to approve or reject it.
    </p>

    <div class="pills">
        <a href="{{ route('applications.index', array_filter(['q' => $search])) }}"
           class="pill {{ $status === null ? 'active' : '' }}">
            All <span class="pill-count">{{ $totalCount }}</span>
        </a>
        @foreach (\App\Models\Member::STATUSES as $s)
            <a href="{{ route('applications.index', array_filter(['q' => $search, 'status' => $s])) }}"
               class="pill {{ $status === $s ? 'active' : '' }}">
                {{ ucfirst($s) }} <span class="pill-count">{{ $counts[$s] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" action="{{ route('applications.index') }}" class="filters">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif

            <input class="form-input" type="search" name="q" value="{{ $search }}" maxlength="100"
                   placeholder="Search name, father name, mobile, email, district or city">
            <button type="submit" class="btn">Search</button>

            @if ($search !== '' || $status)
                <a href="{{ route('applications.index') }}" class="btn btn-light">Clear</a>
            @endif
        </form>

        @if ($applications->isEmpty())
            <p class="empty-state">No applications found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Received</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>District / City</th>
                            <th>Documents</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td>{{ $application->created_at->format('d M Y') }}<br>
                                    <span class="text-muted">{{ $application->created_at->format('h:i A') }}</span></td>
                                <td>
                                    <a href="{{ route('members.show', $application) }}"><strong>{{ $application->full_name }}</strong></a><br>
                                    <span class="text-muted">Father: {{ $application->father_name }}</span>
                                </td>
                                <td>{{ $application->mobile }}</td>
                                <td>{{ $application->district }}<br><span class="text-muted">{{ $application->city }}</span></td>
                                <td>
                                    @php($docs = $application->documentsCount())
                                    <span class="doc-count {{ $application->payment_screenshot_path ? '' : 'doc-missing' }}">
                                        {{ $docs }} of {{ count(\App\Models\Member::DOCUMENT_COLUMNS) }}
                                    </span>
                                    @unless ($application->payment_screenshot_path)
                                        <br><span class="text-muted">No payment screenshot</span>
                                    @endunless
                                </td>
                                <td><span class="badge badge-{{ $application->status }}">{{ ucfirst($application->status) }}</span></td>
                                <td><a href="{{ route('members.show', $application) }}" class="btn btn-light">Review</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span class="text-muted">
                    Showing {{ $applications->firstItem() }} to {{ $applications->lastItem() }} of {{ $applications->total() }}
                </span>
                {{ $applications->links() }}
            </div>
        @endif
    </div>
@endsection
