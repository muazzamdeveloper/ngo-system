@extends('layouts.admin')

@section('title', 'Login activity')

@section('content')
    <h1 class="page-title">Login activity</h1>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-value" data-stat="logins-24h">{{ $last24h['login'] ?? 0 }}</div>
            <div class="stat-label">Logins, last 24 hours</div>
        </div>
        <div class="stat-card {{ ($last24h['failed'] ?? 0) > 0 ? 'stat-attention' : '' }}">
            <div class="stat-value" data-stat="failed-24h">{{ $last24h['failed'] ?? 0 }}</div>
            <div class="stat-label">Failed attempts, last 24 hours</div>
        </div>
        <div class="stat-card {{ ($last24h['lockout'] ?? 0) > 0 ? 'stat-attention' : '' }}">
            <div class="stat-value" data-stat="lockouts-24h">{{ $last24h['lockout'] ?? 0 }}</div>
            <div class="stat-label">Lockouts, last 24 hours</div>
        </div>
    </div>

    <div class="pills">
        <a href="{{ route('login-activity.index', array_filter(['q' => $search])) }}"
           class="pill {{ $event === null ? 'active' : '' }}">All</a>
        @foreach (\App\Models\LoginActivity::LABELS as $key => $label)
            <a href="{{ route('login-activity.index', array_filter(['q' => $search, 'event' => $key])) }}"
               class="pill {{ $event === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" action="{{ route('login-activity.index') }}" class="filters">
            @if ($event)
                <input type="hidden" name="event" value="{{ $event }}">
            @endif

            <input class="form-input" type="search" name="q" value="{{ $search }}" maxlength="100"
                   placeholder="Search name, email or IP address">
            <button type="submit" class="btn">Search</button>

            @if ($search !== '' || $event)
                <a href="{{ route('login-activity.index') }}" class="btn btn-light">Clear</a>
            @endif
        </form>

        @if ($activities->isEmpty())
            <p class="empty-state">No activity recorded yet.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Who</th>
                            <th>Event</th>
                            <th>IP address</th>
                            <th>Browser</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activities as $activity)
                            <tr>
                                <td>{{ $activity->created_at->format('d M Y, H:i:s') }}</td>
                                <td>
                                    @if ($activity->user)
                                        <strong>{{ $activity->user->name }}</strong><br>
                                        <span class="text-muted">{{ $activity->user->email }}</span>
                                    @else
                                        {{ $activity->email ?? 'Unknown' }}<br>
                                        <span class="text-muted">no matching account</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ match ($activity->event) {
                                        'login' => 'approved',
                                        'failed' => 'rejected',
                                        'lockout' => 'blocked',
                                        default => 'deactivated',
                                    } }}">{{ $activity->label() }}</span>
                                </td>
                                <td>{{ $activity->ip_address ?? '-' }}</td>
                                <td title="{{ $activity->user_agent }}">{{ $activity->browser() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span class="text-muted">
                    Showing {{ $activities->firstItem() }} to {{ $activities->lastItem() }} of {{ $activities->total() }}
                </span>
                {{ $activities->links() }}
            </div>
        @endif
    </div>
@endsection
