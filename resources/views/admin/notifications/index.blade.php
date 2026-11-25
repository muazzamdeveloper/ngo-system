@extends('layouts.admin')
@section('title', 'Send notifications')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Send notifications &amp; email alerts</h1>
            <p class="page-subtitle">Email announcements to approved members or pending applicants.</p>
        </div>
        <a href="{{ route('smtp.edit') }}" class="btn btn-light">SMTP config</a>
    </div>

    <div class="stat-grid">
        <div class="stat-card"><div class="stat-body"><div class="stat-label">Total recipients</div><div class="stat-value">{{ $totals['sent'] }}</div></div></div>
        <div class="stat-card"><div class="stat-body"><div class="stat-label">Delivered</div><div class="stat-value">{{ $totals['delivered'] }}</div></div></div>
        <div class="stat-card"><div class="stat-body"><div class="stat-label">Failed</div><div class="stat-value">{{ $totals['failed'] }}</div></div></div>
        <div class="stat-card"><div class="stat-body"><div class="stat-label">Approved members with email</div><div class="stat-value">{{ $totals['audience'] }}</div></div></div>
    </div>

    <div class="split-grid">
        <form method="POST" action="{{ route('notifications.send') }}" class="card form-card">
            @csrf
            <h2 class="section-title">Compose notification</h2>
            <div class="form-group"><label class="form-label" for="audience">Audience <span class="req">*</span></label>
                <select class="form-input" id="audience" name="audience" required>
                    @foreach ($audiences as $value => $label)<option value="{{ $value }}" @selected(old('audience') === $value)>{{ $label }}</option>@endforeach
                </select></div>
            <div class="form-group"><label class="form-label">Category</label>
                <div class="chip-row">
                    @foreach ($categories as $value => $label)
                        <label class="chip"><input type="radio" name="category" value="{{ $value }}" @checked(old('category', 'announcement') === $value) required> {{ $label }}</label>
                    @endforeach
                </div></div>
            <div class="form-group"><label class="form-label" for="subject">Subject / title <span class="req">*</span></label>
                <input class="form-input" id="subject" name="subject" maxlength="255" value="{{ old('subject') }}" required></div>
            <div class="form-group"><label class="form-label" for="body">Message <span class="req">*</span></label>
                <textarea class="form-input" id="body" name="body" rows="6" maxlength="10000" required>{{ old('body') }}</textarea>
                <div class="form-hint">Line breaks are kept in the email.</div></div>
            <div class="form-group"><label class="form-label" for="action_url">Action link (optional)</label>
                <input class="form-input" id="action_url" type="url" name="action_url" maxlength="500" value="{{ old('action_url') }}" placeholder="https://"></div>
            <label class="switch-row"><input type="checkbox" name="via_smtp" value="1" @checked(old('via_smtp', '1'))> <span>Send through the SMTP server (uncheck to use the default mailer)</span></label>
            <div class="form-actions"><button type="submit" class="btn btn-block">Send notification now</button></div>
        </form>

        <div class="card table-card">
            <div class="card-head"><h2 class="card-title">Notification history</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Subject</th><th>Audience</th><th>Sent</th><th>Failed</th><th>When</th></tr></thead>
                    <tbody>
                        @forelse ($history as $n)
                            <tr>
                                <td><span class="strong">{{ $n->subject }}</span><div class="muted small">{{ \App\Models\EmailNotification::CATEGORIES[$n->category] ?? $n->category }}</div></td>
                                <td class="small">{{ \App\Models\EmailNotification::AUDIENCES[$n->audience] ?? $n->audience }}</td>
                                <td>{{ $n->sent_count }} / {{ $n->recipients }}</td>
                                <td>{{ $n->failed_count }}</td>
                                <td class="small">{{ $n->created_at?->format('d M Y, h:i A') }}<div class="muted">{{ $n->sender?->name }}</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-cell">No notifications sent yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-footer">{{ $history->links('vendor.pagination.custom') }}</div>
        </div>
    </div>
@endsection
