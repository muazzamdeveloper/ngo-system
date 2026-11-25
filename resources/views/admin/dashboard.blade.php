@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @include('admin.partials.dashboard-programmes')
    <div class="dash-head">
        <h1 class="page-title"><x-admin-icon name="chart" /> Admin Dashboard Overview</h1>
        <p class="dash-welcome">Welcome back, {{ auth()->user()->name }}. Here is what is happening today.</p>
    </div>

    @if ($members === null && $staff === null)
        <div class="card">
            <h2>Welcome, {{ auth()->user()->name }}</h2>
            <p class="text-muted">You have access to the admin panel. Your menu shows the areas you can use.</p>
        </div>
    @endif

    @if ($members !== null)
        <div class="stat-grid">
            <a href="{{ route('applications.index', ['status' => 'pending']) }}"
               class="stat-card {{ $members['online_pending'] > 0 ? 'stat-attention' : '' }}">
                <span class="stat-icon tone-amber"><x-admin-icon name="inbox" /></span>
                <div class="stat-body">
                    <div class="stat-label">New online applications</div>
                    <div class="stat-value" data-stat="online-pending">{{ $members['online_pending'] }}</div>
                </div>
            </a>

            <a href="{{ route('members.index', ['status' => 'pending']) }}"
               class="stat-card {{ ($members['counts']['pending'] ?? 0) > 0 ? 'stat-attention' : '' }}">
                <span class="stat-icon tone-amber"><x-admin-icon name="clock" /></span>
                <div class="stat-body">
                    <div class="stat-label">Pending applications</div>
                    <div class="stat-value" data-stat="pending">{{ $members['counts']['pending'] ?? 0 }}</div>
                </div>
            </a>

            <a href="{{ route('members.index', ['status' => 'approved']) }}" class="stat-card">
                <span class="stat-icon tone-green"><x-admin-icon name="check" /></span>
                <div class="stat-body">
                    <div class="stat-label">Approved members</div>
                    <div class="stat-value" data-stat="approved">{{ $members['counts']['approved'] ?? 0 }}</div>
                </div>
            </a>

            <a href="{{ route('members.index') }}" class="stat-card stat-solid">
                <span class="stat-icon"><x-admin-icon name="users" /></span>
                <div class="stat-body">
                    <div class="stat-label">All members</div>
                    <div class="stat-value" data-stat="total">{{ $members['total'] }}</div>
                </div>
            </a>

            <div class="stat-card">
                <span class="stat-icon tone-blue"><x-admin-icon name="user-plus" /></span>
                <div class="stat-body">
                    <div class="stat-label">Added this month</div>
                    <div class="stat-value" data-stat="new-this-month">{{ $members['new_this_month'] }}</div>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icon tone-green"><x-admin-icon name="award" /></span>
                <div class="stat-body">
                    <div class="stat-label">Approved this month</div>
                    <div class="stat-value" data-stat="approved-this-month">{{ $members['approved_this_month'] }}</div>
                </div>
            </div>

            @if ($staff !== null)
                <a href="{{ route('users.index') }}" class="stat-card">
                    <span class="stat-icon tone-blue"><x-admin-icon name="shield" /></span>
                    <div class="stat-body">
                        <div class="stat-label">Active staff users</div>
                        <div class="stat-value" data-stat="staff">{{ $staff['active'] }}<span class="stat-sub"> / {{ $staff['total'] }}</span></div>
                    </div>
                </a>
            @endif
        </div>

        <div class="dash-grid">
            {{-- New members per month --}}
            <div class="card dash-wide">
                <h2 class="card-title"><x-admin-icon name="chart" /> New members (last 6 months)</h2>
                @php($trendMax = max(1, max(array_column($members['trend'], 'count'))))
                <div class="trend">
                    @foreach ($members['trend'] as $point)
                        <div class="trend-col">
                            <span class="trend-num">{{ $point['count'] }}</span>
                            <span class="trend-bar" style="height: {{ max(4, (int) round($point['count'] / $trendMax * 100)) }}%"></span>
                            <span class="trend-label">{{ $point['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Members by status --}}
            <div class="card">
                <h2 class="card-title"><x-admin-icon name="users" /> Members by status</h2>

                @if ($members['total'] === 0)
                    <p class="empty-state">No members yet.</p>
                @else
                    @foreach (\App\Models\Member::STATUSES as $status)
                        @php($count = $members['counts'][$status] ?? 0)
                        @php($percent = (int) round($count / $members['total'] * 100))
                        <div class="bar-row">
                            <div class="bar-head">
                                <a href="{{ route('members.index', ['status' => $status]) }}">{{ ucfirst($status) }}</a>
                                <span class="text-muted">{{ $count }} ({{ $percent }}%)</span>
                            </div>
                            <div class="bar"><span class="bar-fill bar-{{ $status }}" style="width: {{ $percent }}%"></span></div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Recently added --}}
            <div class="card dash-wide">
                <div class="card-head">
                    <h2 class="card-title"><x-admin-icon name="user-plus" /> Recently added members</h2>
                    <a href="{{ route('members.index') }}" class="card-link">View all</a>
                </div>

                @if ($members['recent']->isEmpty())
                    <p class="empty-state">No members yet.</p>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>City</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($members['recent'] as $recent)
                                    <tr>
                                        <td><a href="{{ route('members.show', $recent) }}"><strong>{{ $recent->full_name }}</strong></a></td>
                                        <td>{{ $recent->city }}</td>
                                        <td>{{ $recent->created_at->format('d M Y') }}</td>
                                        <td><span class="badge badge-{{ $recent->status }}">{{ ucfirst($recent->status) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Quick actions + public application link --}}
            <div class="dash-side">
                <div class="card">
                    <h2 class="card-title"><x-admin-icon name="activity" /> Quick actions</h2>
                    <div class="quick-actions">
                        @can('members.create')
                            <a href="{{ route('members.create') }}" class="quick-btn"><x-admin-icon name="plus" /> Add member</a>
                        @endcan
                        <a href="{{ route('applications.index') }}" class="quick-btn"><x-admin-icon name="inbox" /> Applications</a>
                        <a href="{{ route('members.index') }}" class="quick-btn"><x-admin-icon name="users" /> All members</a>
                        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="quick-btn"><x-admin-icon name="external" /> View website</a>
                    </div>
                </div>

                <div class="card">
                    <h2 class="card-title"><x-admin-icon name="link" /> Member registration link</h2>
                    <input type="text" class="form-input" readonly value="{{ route('apply.create') }}" onclick="this.select()">
                    <div class="quick-actions link-actions">
                        <button type="button" class="quick-btn" data-copy="{{ route('apply.create') }}"><x-admin-icon name="copy" /> <span>Copy link</span></button>
                        <a href="{{ route('apply.create') }}" target="_blank" rel="noopener" class="quick-btn"><x-admin-icon name="external" /> <span>Open form</span></a>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($staff !== null)
        <div class="stat-grid">
            <a href="{{ route('users.index') }}" class="stat-card">
                <span class="stat-icon tone-blue"><x-admin-icon name="shield" /></span>
                <div class="stat-body">
                    <div class="stat-label">Active staff users</div>
                    <div class="stat-value" data-stat="staff">{{ $staff['active'] }}<span class="stat-sub"> / {{ $staff['total'] }}</span></div>
                </div>
            </a>
        </div>
    @endif
@endsection
