<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Every block is only built (and shown) for people allowed to see that data.
        $members = $user->can('members.view') ? $this->memberStats() : null;

        $staff = $user->can('users.manage')
            ? [
                'total' => User::staff()->count(),
                'active' => User::staff()->where('is_active', true)->count(),
            ]
            : null;

        $programmes = $this->programmeStats($user);

        return view('admin.dashboard', ['members' => $members, 'staff' => $staff, 'programmes' => $programmes]);
    }

    /** Donations, campaigns and content numbers. Built only for people who may open those areas. */
    private function programmeStats($user): ?array
    {
        if (! $user->can('donations.manage') && ! $user->can('campaigns.manage') && ! $user->can('events.manage') && ! $user->can('volunteers.review')) {
            return null;
        }

        $completed = \App\Models\Donation::query()->where('status', 'completed');

        return [
            'collected' => (float) (clone $completed)->sum('amount'),
            'donations_count' => (clone $completed)->count(),
            'pending_donations' => \App\Models\Donation::query()->where('status', 'pending')->count(),
            'pending_amount' => (float) \App\Models\Donation::query()->where('status', 'pending')->sum('amount'),
            'active_campaigns' => \App\Models\Campaign::query()->where('status', 'active')->count(),
            'upcoming_events' => \App\Models\Event::query()->where('status', 'active')->whereDate('event_date', '>=', today())->count(),
            'pending_volunteers' => \App\Models\Volunteer::query()->where('status', 'pending')->count(),
            'gallery_items' => \App\Models\GalleryPhoto::query()->count(),
            'campaigns' => \App\Models\Campaign::query()->where('status', 'active')->orderByDesc('id')->limit(5)->get(),
            'recent_donations' => \App\Models\Donation::query()->with('campaign')->orderByDesc('donated_at')->limit(5)->get(),
        ];
    }

    private function memberStats(): array
    {
        $counts = Member::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $monthStart = now()->startOfMonth();

        return [
            'counts' => $counts,
            'total' => (int) $counts->sum(),
            'new_this_month' => Member::where('created_at', '>=', $monthStart)->count(),
            'approved_this_month' => Member::where('approved_at', '>=', $monthStart)->count(),
            'online_pending' => Member::online()->pending()->count(),
            'trend' => $this->monthlyTrend(6),
            'recent' => Member::query()
                ->latest('id')
                ->limit(5)
                ->get(['id', 'full_name', 'city', 'status', 'membership_number', 'created_at']),
        ];
    }

    /**
     * New members per month for the last $months months (oldest first).
     * One small count query per month, so it works the same on MySQL and SQLite.
     */
    private function monthlyTrend(int $months): array
    {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonthsNoOverflow($i);
            $end = $start->copy()->endOfMonth();

            $trend[] = [
                'label' => $start->format('M'),
                'count' => Member::whereBetween('created_at', [$start, $end])->count(),
            ];
        }

        return $trend;
    }
}
