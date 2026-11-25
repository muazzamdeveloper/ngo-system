<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardSetting;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Staff check of the application-fee payment (paid by QR, proved by a screenshot).
 * This is only for the application fee. Donations are a separate module.
 *
 * State of a payment:
 *   verified / rejected = payment_status column
 *   pending             = payment_status is empty AND a screenshot exists
 *   none                = payment_status is empty AND no screenshot
 * The status is informational: it never blocks approving a member.
 */
class ApplicationPaymentController extends Controller
{
    private const PER_PAGE = 15;

    private const FILTERS = ['pending', 'verified', 'rejected', 'none'];

    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $filter = $request->query('filter');
        $filter = in_array($filter, self::FILTERS, true) ? $filter : null;

        $counts = [];
        foreach (self::FILTERS as $name) {
            $counts[$name] = $this->applyFilter($this->base(), $name)->count();
        }

        $payments = $this->applyFilter($this->base(), $filter)
            ->when($search !== '', function (Builder $query) use ($search) {
                // Escape % and _ so the typed text is matched literally.
                $like = '%' . addcslashes($search, '\\%_') . '%';

                $query->where(function (Builder $query) use ($like) {
                    $query->where('full_name', 'like', $like)
                        ->orWhere('father_name', 'like', $like)
                        ->orWhere('mobile', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            // Payments waiting for a check first, then newest first.
            ->orderByRaw('CASE WHEN payment_status IS NULL AND payment_screenshot_path IS NOT NULL THEN 0 ELSE 1 END')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $checkers = User::query()
            ->whereIn('id', $payments->pluck('payment_checked_by')->filter()->unique())
            ->pluck('name', 'id');

        return view('admin.payments.index', [
            'payments' => $payments,
            'counts' => $counts,
            'totalCount' => array_sum($counts),
            'filter' => $filter,
            'search' => $search,
            'checkers' => $checkers,
            'feeLabel' => CardSetting::current()->feeLabel(),
        ]);
    }

    public function screenshot(Member $member): StreamedResponse
    {
        $path = $member->payment_screenshot_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function verify(Request $request, Member $member): RedirectResponse
    {
        return $this->mark($request, $member, 'verified', null, 'Payment marked as verified.');
    }

    public function reject(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:255'],
        ], [
            'note.required' => 'Please write a short reason for rejecting the payment.',
        ]);

        return $this->mark($request, $member, 'rejected', trim($data['note']), 'Payment marked as rejected.');
    }

    /** Back to "not checked yet". */
    public function reset(Request $request, Member $member): RedirectResponse
    {
        abort_unless($this->isPaymentRecord($member), 404);

        $member->forceFill([
            'payment_status' => null,
            'payment_note' => null,
            'payment_checked_at' => null,
            'payment_checked_by' => null,
        ])->save();

        Log::info('Application payment reset', ['member_id' => $member->id, 'user_id' => $request->user()->id]);

        return back()->with('success', 'Payment check cleared.');
    }

    private function mark(Request $request, Member $member, string $status, ?string $note, string $message): RedirectResponse
    {
        abort_unless($this->isPaymentRecord($member), 404);

        // forceFill: these columns are deliberately not mass-assignable.
        $member->forceFill([
            'payment_status' => $status,
            'payment_note' => $note,
            'payment_checked_at' => now(),
            'payment_checked_by' => $request->user()->id,
        ])->save();

        Log::info('Application payment checked', [
            'member_id' => $member->id,
            'status' => $status,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', $message);
    }

    /** Online applications, plus any member who has a payment screenshot. */
    private function base(): Builder
    {
        return Member::query()
            ->select([
                'id', 'full_name', 'father_name', 'mobile', 'city', 'district', 'status', 'source', 'created_at',
                'payment_screenshot_path', 'payment_status', 'payment_note', 'payment_checked_at', 'payment_checked_by',
            ])
            ->where(function (Builder $query) {
                $query->where('source', Member::SOURCE_ONLINE)
                    ->orWhereNotNull('payment_screenshot_path');
            });
    }

    private function applyFilter(Builder $query, ?string $filter): Builder
    {
        return match ($filter) {
            'pending' => $query->whereNull('payment_status')->whereNotNull('payment_screenshot_path'),
            'verified' => $query->where('payment_status', 'verified'),
            'rejected' => $query->where('payment_status', 'rejected'),
            'none' => $query->whereNull('payment_status')->whereNull('payment_screenshot_path'),
            default => $query,
        };
    }

    private function isPaymentRecord(Member $member): bool
    {
        return $member->source === Member::SOURCE_ONLINE || filled($member->payment_screenshot_path);
    }
}
