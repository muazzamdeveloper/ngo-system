<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginActivityController extends Controller
{
    public function index(Request $request): View
    {
        $event = $request->query('event');
        $event = in_array($event, LoginActivity::EVENTS, true) ? $event : null;

        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $activities = LoginActivity::query()
            ->with('user:id,name,email')
            ->when($event, fn ($query) => $query->where('event', $event))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . addcslashes($search, '\\%_') . '%';

                $query->where(function ($query) use ($like) {
                    $query->where('email', 'like', $like)
                        ->orWhere('ip_address', 'like', $like)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like));
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $last24h = LoginActivity::query()
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('event, count(*) as total')
            ->groupBy('event')
            ->pluck('total', 'event');

        return view('admin.login-activity.index', [
            'activities' => $activities,
            'event' => $event,
            'search' => $search,
            'last24h' => $last24h,
        ]);
    }
}
