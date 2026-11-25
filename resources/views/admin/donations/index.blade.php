@extends('layouts.admin')

@section('title', 'Donations log')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Donations log</h1>
            <p class="page-subtitle">Record donations, confirm payments and export the ledger.</p>
        </div>
        <div class="actions-row">
            <a href="{{ route('donations.export', request()->query()) }}" class="btn btn-light">Export CSV</a>
            <a href="{{ route('donations.create') }}" class="btn">+ Add donation</a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-body">
                <div class="stat-label">Total collected</div>
                <div class="stat-value">&#8377;{{ number_format($stats['collected'], 2) }}</div>
                <div class="muted small">{{ $stats['completed'] }} completed from {{ $stats['donors'] }} donor(s)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-body">
                <div class="stat-label">Pending approval</div>
                <div class="stat-value">{{ $stats['pending'] }}</div>
                <div class="muted small">&#8377;{{ number_format($stats['pending_amount'], 2) }} waiting to be confirmed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-body">
                <div class="stat-label">Failed / cancelled</div>
                <div class="stat-value">{{ $stats['failed'] }}</div>
                <div class="muted small">Needs a follow-up with the donor</div>
            </div>
        </div>
    </div>

    <div class="pills">
        <a href="{{ route('donations.index', array_filter(['q' => $filters['q'], 'from' => $filters['from'], 'to' => $filters['to']])) }}" class="pill {{ $filters['status'] === null ? 'active' : '' }}">All <span class="pill-count">{{ $counts['all'] }}</span></a>
        <a href="{{ route('donations.index', array_filter(['status' => 'completed', 'q' => $filters['q'], 'from' => $filters['from'], 'to' => $filters['to']])) }}" class="pill {{ $filters['status'] === 'completed' ? 'active' : '' }}">Completed <span class="pill-count">{{ $counts['completed'] }}</span></a>
        <a href="{{ route('donations.index', array_filter(['status' => 'pending', 'q' => $filters['q'], 'from' => $filters['from'], 'to' => $filters['to']])) }}" class="pill {{ $filters['status'] === 'pending' ? 'active' : '' }}">Pending <span class="pill-count">{{ $counts['pending'] }}</span></a>
        <a href="{{ route('donations.index', array_filter(['status' => 'failed', 'q' => $filters['q'], 'from' => $filters['from'], 'to' => $filters['to']])) }}" class="pill {{ $filters['status'] === 'failed' ? 'active' : '' }}">Failed <span class="pill-count">{{ $counts['failed'] }}</span></a>
    </div>

    <div class="card table-card">
        <form method="GET" action="{{ route('donations.index') }}" class="toolbar">
            <input class="form-input toolbar-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search name, email, phone, PAN, receipt or reference…" aria-label="Search donations">
            <input class="form-input" type="date" name="from" value="{{ $filters['from'] }}" aria-label="From date">
            <input class="form-input" type="date" name="to" value="{{ $filters['to'] }}" aria-label="To date">
            @if ($filters['status'])<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
            <button type="submit" class="btn btn-light">Filter</button>
            <a href="{{ route('donations.index') }}" class="btn btn-light">Reset</a>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Donor</th>
                        <th>Campaign</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($donations as $donation)
                        <tr>
                            <td><span class="strong">{{ $donation->receipt_no }}</span>
                                @if ($donation->source === 'public')<div class="muted small">From website</div>@endif</td>
                            <td>
                                <span class="strong">{{ $donation->donor_name }}</span>
                                <div class="muted small">{{ $donation->donor_phone ?: '—' }}@if($donation->donor_email) · {{ $donation->donor_email }}@endif</div>
                                @if($donation->donor_pan)<div class="muted small">PAN {{ $donation->donor_pan }}</div>@endif
                            </td>
                            <td>{{ $donation->campaign?->title ?? 'General support' }}</td>
                            <td><span class="money">&#8377;{{ number_format((float) $donation->amount, 2) }}</span></td>
                            <td><span class="tag">{{ $donation->methodLabel() }}</span>@if($donation->transaction_ref)<div class="muted small">{{ $donation->transaction_ref }}</div>@endif</td>
                            <td class="small">{{ $donation->donated_at?->format('d M Y, h:i A') }}</td>
                            <td><span class="status-badge status-{{ $donation->status }}">{{ \App\Models\Donation::STATUSES[$donation->status] ?? $donation->status }}</span></td>
                            <td class="col-actions">
                                <div class="row-actions">
                                    <a href="{{ route('donations.edit', $donation) }}" class="btn btn-small btn-light">Edit</a>
                                    <form method="POST" action="{{ route('donations.status', $donation) }}" class="inline-form">
                                        @csrf
                                        <select name="status" class="form-input form-input-small" aria-label="Change status" onchange="this.form.submit()">
                                            @foreach (\App\Models\Donation::STATUSES as $value => $label)
                                                <option value="{{ $value }}" @selected($donation->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ route('donations.destroy', $donation) }}" onsubmit="return confirm('Delete donation {{ $donation->receipt_no }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-cell">No donations match these filters. <a href="{{ route('donations.create') }}">Record a donation</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">{{ $donations->links('vendor.pagination.custom') }}</div>
    </div>
@endsection
