@if (! empty($programmes))
    <section class="programme-block" aria-label="Donations and programmes">
        <div class="stat-grid">
            <div class="stat-card">
                <span class="stat-icon tone-green"><x-admin-icon name="heart" /></span>
                <div class="stat-body">
                    <div class="stat-label">Donations raised</div>
                    <div class="stat-value">&#8377;{{ number_format($programmes['collected'], 2) }}</div>
                    <div class="muted small">{{ $programmes['donations_count'] }} completed donation(s)</div>
                </div>
            </div>
            <a href="{{ route('donations.index', ['status' => 'pending']) }}" class="stat-card {{ $programmes['pending_donations'] > 0 ? 'stat-attention' : '' }}">
                <span class="stat-icon tone-amber"><x-admin-icon name="clock" /></span>
                <div class="stat-body">
                    <div class="stat-label">Pending donations</div>
                    <div class="stat-value">{{ $programmes['pending_donations'] }}</div>
                    <div class="muted small">&#8377;{{ number_format($programmes['pending_amount'], 2) }} to confirm</div>
                </div>
            </a>
            <a href="{{ route('modules.index', 'campaigns') }}" class="stat-card">
                <span class="stat-icon tone-blue"><x-admin-icon name="megaphone" /></span>
                <div class="stat-body">
                    <div class="stat-label">Active campaigns</div>
                    <div class="stat-value">{{ $programmes['active_campaigns'] }}</div>
                </div>
            </a>
            <a href="{{ route('modules.index', 'events') }}" class="stat-card">
                <span class="stat-icon tone-blue"><x-admin-icon name="calendar" /></span>
                <div class="stat-body">
                    <div class="stat-label">Upcoming events</div>
                    <div class="stat-value">{{ $programmes['upcoming_events'] }}</div>
                </div>
            </a>
            <a href="{{ route('volunteers.index', ['status' => 'pending']) }}" class="stat-card {{ $programmes['pending_volunteers'] > 0 ? 'stat-attention' : '' }}">
                <span class="stat-icon tone-amber"><x-admin-icon name="user-plus" /></span>
                <div class="stat-body">
                    <div class="stat-label">Volunteers to review</div>
                    <div class="stat-value">{{ $programmes['pending_volunteers'] }}</div>
                </div>
            </a>
        </div>

        <div class="dash-two-col">
            <div class="card">
                <div class="card-head"><h2 class="card-title">Campaign progress</h2><a href="{{ route('modules.index', 'campaigns') }}" class="card-link">All</a></div>
                @forelse ($programmes['campaigns'] as $campaign)
                    <div class="campaign-row">
                        <div class="campaign-row-head"><span class="strong">{{ $campaign->title }}</span><span class="muted small">{{ $campaign->progress }}%</span></div>
                        <div class="progress" aria-hidden="true"><span style="width: {{ $campaign->progress }}%"></span></div>
                        <div class="muted small">Raised &#8377;{{ number_format($campaign->raised, 0) }} of &#8377;{{ number_format((float) $campaign->goal_amount, 0) }}</div>
                    </div>
                @empty
                    <p class="muted">No active campaigns. <a href="{{ route('modules.create', 'campaigns') }}">Create one</a>.</p>
                @endforelse
            </div>

            <div class="card">
                <div class="card-head"><h2 class="card-title">Recent donations</h2><a href="{{ route('donations.index') }}" class="card-link">All</a></div>
                <ul class="plain-list">
                    @forelse ($programmes['recent_donations'] as $donation)
                        <li>
                            <span class="strong">{{ $donation->donor_name }}</span>
                            <span class="muted small">{{ $donation->campaign?->title ?? 'General support' }}</span>
                            <span class="money">&#8377;{{ number_format((float) $donation->amount, 2) }}</span>
                            <span class="status-badge status-{{ $donation->status }}">{{ \App\Models\Donation::STATUSES[$donation->status] ?? $donation->status }}</span>
                        </li>
                    @empty
                        <li class="muted">No donations recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card quick-actions">
            <h2 class="card-title">Quick actions</h2>
            <div class="actions-row">
                @can('campaigns.manage')<a class="btn btn-light" href="{{ route('modules.create', 'campaigns') }}">+ New campaign</a>@endcan
                @can('events.manage')<a class="btn btn-light" href="{{ route('modules.create', 'events') }}">+ New event</a>@endcan
                @can('gallery.manage')<a class="btn btn-light" href="{{ route('modules.create', 'gallery') }}">+ Upload photo</a>@endcan
                @can('donations.manage')<a class="btn btn-light" href="{{ route('donations.create') }}">+ Record donation</a>@endcan
                @can('volunteers.review')<a class="btn btn-light" href="{{ route('volunteers.index') }}">Review volunteers</a>@endcan
                @can('email.manage')<a class="btn btn-light" href="{{ route('notifications.index') }}">Send notification</a>@endcan
                <a class="btn btn-light" href="{{ route('home') }}" target="_blank" rel="noopener">View website</a>
            </div>
        </div>
    </section>
@endif
