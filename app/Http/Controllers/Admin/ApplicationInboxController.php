<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff inbox of applications sent through the public /apply form.
 * It only lists them; Approve / Reject stay on the member profile (Part 2 workflow).
 */
class ApplicationInboxController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        // An unknown status in the URL is simply ignored.
        $status = $request->query('status');
        $status = in_array($status, Member::STATUSES, true) ? $status : null;

        $applications = Member::query()
            ->online()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                // Escape % and _ so the visitor's text is matched literally.
                $like = '%' . addcslashes($search, '\\%_') . '%';

                $query->where(function ($query) use ($like) {
                    $query->where('full_name', 'like', $like)
                        ->orWhere('father_name', 'like', $like)
                        ->orWhere('mobile', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('district', 'like', $like)
                        ->orWhere('city', 'like', $like);
                });
            })
            // Waiting applications first, then newest first.
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [Member::STATUS_PENDING])
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $counts = Member::query()
            ->online()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.applications.index', [
            'applications' => $applications,
            'search' => $search,
            'status' => $status,
            'counts' => $counts,
            'totalCount' => (int) $counts->sum(),
        ]);
    }
}
