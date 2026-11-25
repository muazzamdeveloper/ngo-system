<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Volunteer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VolunteerAdminController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('volunteers.review');

        $status = array_key_exists((string) $request->query('status'), Volunteer::STATUSES) ? (string) $request->query('status') : null;
        $search = trim((string) $request->query('q', ''));

        $query = Volunteer::query()->status($status)->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(fn (Builder $q) => $q->where('full_name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('mobile', 'like', $like)->orWhere('city', 'like', $like));
        }

        return view('admin.volunteers.index', [
            'volunteers' => $query->paginate(20)->withQueryString(),
            'counts' => [
                'all' => Volunteer::query()->count(),
                'pending' => Volunteer::query()->where('status', 'pending')->count(),
                'approved' => Volunteer::query()->where('status', 'approved')->count(),
                'rejected' => Volunteer::query()->where('status', 'rejected')->count(),
            ],
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function review(Request $request, Volunteer $volunteer): RedirectResponse
    {
        Gate::authorize('volunteers.review');

        $status = $request->validate(['status' => ['required', 'in:pending,approved,rejected']])['status'];

        $volunteer->update([
            'status' => $status,
            'reviewed_at' => $status === 'pending' ? null : now(),
            'reviewed_by' => $status === 'pending' ? null : $request->user()->id,
        ]);

        return back()->with('success', "{$volunteer->full_name} marked as {$status}.");
    }

    public function destroy(Volunteer $volunteer): RedirectResponse
    {
        Gate::authorize('volunteers.review');

        $name = $volunteer->full_name;
        $volunteer->delete();

        return redirect()->route('volunteers.index')->with('success', "Volunteer {$name} removed.");
    }
}
