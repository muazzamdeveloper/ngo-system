@extends('layouts.admin')

@section('title', 'Application payments')

@section('content')
    <div class="page-header">
        <h1 class="page-title"><x-admin-icon name="card" /> Application payments</h1>
    </div>

    <p class="text-muted" style="margin-bottom: 1rem;">
        Applicants pay the application fee by QR code and upload a screenshot. Check each screenshot here and mark it
        verified or rejected.
        @if ($feeLabel)
            <strong>{{ $feeLabel }}</strong>.
        @endif
        This is only for the application fee, not for donations. The result is a note for your team and does not block
        approving a member.
    </p>

    <div class="pills">
        <a href="{{ route('payments.index', array_filter(['q' => $search])) }}"
           class="pill {{ $filter === null ? 'active' : '' }}">
            All <span class="pill-count">{{ $totalCount }}</span>
        </a>
        @foreach (['pending' => 'To check', 'verified' => 'Verified', 'rejected' => 'Rejected', 'none' => 'No screenshot'] as $key => $label)
            <a href="{{ route('payments.index', array_filter(['q' => $search, 'filter' => $key])) }}"
               class="pill {{ $filter === $key ? 'active' : '' }}">
                {{ $label }} <span class="pill-count">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" action="{{ route('payments.index') }}" class="filters">
            @if ($filter)
                <input type="hidden" name="filter" value="{{ $filter }}">
            @endif

            <input class="form-input" type="search" name="q" value="{{ $search }}" maxlength="100"
                   placeholder="Search name, father name, mobile or email">
            <button type="submit" class="btn">Search</button>

            @if ($search !== '' || $filter)
                <a href="{{ route('payments.index') }}" class="btn btn-light">Clear</a>
            @endif
        </form>

        @error('note')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        @if ($payments->isEmpty())
            <p class="empty-state">No payments found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Received</th>
                            <th>Applicant</th>
                            <th>Screenshot</th>
                            <th>Payment</th>
                            <th>Checked</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            @php
                                $state = $payment->payment_status
                                    ?? ($payment->payment_screenshot_path ? 'pending' : 'none');
                            @endphp
                            <tr>
                                <td>{{ $payment->created_at->format('d M Y') }}<br>
                                    <span class="text-muted">{{ $payment->created_at->format('h:i A') }}</span></td>
                                <td>
                                    @can('members.view')
                                        <a href="{{ route('members.show', $payment) }}"><strong>{{ $payment->full_name }}</strong></a>
                                    @else
                                        <strong>{{ $payment->full_name }}</strong>
                                    @endcan
                                    <br><span class="text-muted">{{ $payment->mobile }}</span>
                                </td>
                                <td>
                                    @if ($payment->payment_screenshot_path)
                                        <a href="{{ route('payments.screenshot', $payment) }}" target="_blank" rel="noopener">
                                            <img src="{{ route('payments.screenshot', $payment) }}" alt="Payment screenshot of {{ $payment->full_name }}"
                                                 loading="lazy"
                                                 style="height: 64px; width: auto; max-width: 110px; object-fit: cover; border-radius: 8px; border: 1px solid var(--color-border);">
                                        </a>
                                    @else
                                        <span class="text-muted">None uploaded</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($state === 'verified')
                                        <span class="badge badge-approved">Verified</span>
                                    @elseif ($state === 'rejected')
                                        <span class="badge badge-rejected">Rejected</span>
                                    @elseif ($state === 'pending')
                                        <span class="badge badge-pending">To check</span>
                                    @else
                                        <span class="badge badge-deactivated">No screenshot</span>
                                    @endif

                                    @if ($payment->payment_note)
                                        <br><span class="text-muted">{{ $payment->payment_note }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->payment_checked_at)
                                        {{ $payment->payment_checked_at->format('d M Y') }}<br>
                                        <span class="text-muted">{{ $checkers[$payment->payment_checked_by] ?? '' }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @can('payments.verify')
                                        <div class="pay-actions">
                                            @if ($state !== 'verified')
                                                <form method="POST" action="{{ route('payments.verify', $payment) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-small">Verify</button>
                                                </form>
                                            @endif

                                            @if ($state !== 'rejected')
                                                <details class="pay-reject">
                                                    <summary class="btn btn-light btn-small">Reject</summary>
                                                    <form method="POST" action="{{ route('payments.reject', $payment) }}">
                                                        @csrf
                                                        <input class="form-input" type="text" name="note" maxlength="255" required
                                                               placeholder="Reason (required)">
                                                        <button type="submit" class="btn btn-danger btn-small">Confirm reject</button>
                                                    </form>
                                                </details>
                                            @endif

                                            @if ($payment->payment_status)
                                                <form method="POST" action="{{ route('payments.reset', $payment) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-light btn-small">Undo</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span class="text-muted">
                    Showing {{ $payments->firstItem() }} to {{ $payments->lastItem() }} of {{ $payments->total() }}
                </span>
                {{ $payments->links() }}
            </div>
        @endif
    </div>

    <style>
        .pay-actions { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: flex-start; }
        .pay-reject summary { list-style: none; cursor: pointer; }
        .pay-reject summary::-webkit-details-marker { display: none; }
        .pay-reject form { display: grid; gap: 0.4rem; min-width: 210px; margin-top: 0.4rem; }
    </style>
@endsection
