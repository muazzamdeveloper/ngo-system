<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

/**
 * Donations ledger: record, review and change the status of donations, with totals and CSV export.
 */
class DonationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('donations.manage');

        $filters = $this->filters($request);
        $query = $this->filtered($filters);

        return view('admin.donations.index', [
            'donations' => $query->with('campaign')->orderByDesc('donated_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'stats' => $this->stats(),
            'counts' => [
                'all' => Donation::query()->count(),
                'completed' => Donation::query()->where('status', 'completed')->count(),
                'pending' => Donation::query()->where('status', 'pending')->count(),
                'failed' => Donation::query()->whereIn('status', ['failed', 'cancelled'])->count(),
            ],
            'filters' => $filters,
            'campaigns' => Campaign::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('donations.manage');

        return view('admin.donations.form', [
            'donation' => new Donation(['status' => 'completed', 'method' => 'upi', 'donated_at' => now()]),
            'campaigns' => Campaign::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('donations.manage');

        $donation = new Donation();
        $donation->fill($this->validated($request) + ['source' => 'admin', 'created_by' => $request->user()->id]);
        $donation->save();

        return redirect()->route('donations.index')->with('success', "Donation {$donation->receipt_no} recorded.");
    }

    public function edit(Donation $donation): View
    {
        Gate::authorize('donations.manage');

        return view('admin.donations.form', [
            'donation' => $donation,
            'campaigns' => Campaign::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(Request $request, Donation $donation): RedirectResponse
    {
        Gate::authorize('donations.manage');

        $donation->fill($this->validated($request))->save();

        return redirect()->route('donations.index')->with('success', 'Donation updated.');
    }

    public function status(Request $request, Donation $donation): RedirectResponse
    {
        Gate::authorize('donations.manage');

        $status = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(Donation::STATUSES))]])['status'];
        $donation->update(['status' => $status]);

        return back()->with('success', "Donation {$donation->receipt_no} marked as " . strtolower(Donation::STATUSES[$status]) . '.');
    }

    public function destroy(Donation $donation): RedirectResponse
    {
        Gate::authorize('donations.manage');

        $receipt = $donation->receipt_no;
        $donation->delete();

        return redirect()->route('donations.index')->with('success', "Donation {$receipt} deleted.");
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('donations.manage');

        $query = $this->filtered($this->filters($request))->with('campaign')->orderBy('donated_at')->orderBy('id');
        $filename = 'donations-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Receipt', 'Date', 'Donor', 'Email', 'Phone', 'PAN', 'Campaign', 'Amount', 'Method', 'Status', 'Reference']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $d) {
                    // Guard against spreadsheet formula injection in text fields.
                    $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;

                    fputcsv($out, [
                        $d->receipt_no,
                        $d->donated_at?->format('Y-m-d H:i'),
                        $safe($d->donor_name),
                        $safe($d->donor_email),
                        $d->donor_phone,
                        $d->donor_pan,
                        $safe($d->campaign?->title),
                        number_format((float) $d->amount, 2, '.', ''),
                        $d->methodLabel(),
                        Donation::STATUSES[$d->status] ?? $d->status,
                        $safe($d->transaction_ref),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    private function filters(Request $request): array
    {
        $status = $request->query('status');

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => array_key_exists((string) $status, Donation::STATUSES) ? (string) $status : null,
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
        ];
    }

    private function filtered(array $f): Builder
    {
        $query = Donation::query();

        if ($f['q'] !== '') {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->where('donor_name', 'like', $like)
                    ->orWhere('donor_email', 'like', $like)
                    ->orWhere('donor_phone', 'like', $like)
                    ->orWhere('donor_pan', 'like', $like)
                    ->orWhere('receipt_no', 'like', $like)
                    ->orWhere('transaction_ref', 'like', $like);
            });
        }

        if ($f['status']) {
            $query->where('status', $f['status']);
        }

        if ($f['from']) {
            $query->whereDate('donated_at', '>=', $f['from']);
        }

        if ($f['to']) {
            $query->whereDate('donated_at', '<=', $f['to']);
        }

        return $query;
    }

    private function stats(): array
    {
        $completed = Donation::query()->where('status', 'completed');

        return [
            'collected' => (float) (clone $completed)->sum('amount'),
            'completed' => (clone $completed)->count(),
            'donors' => (clone $completed)->distinct('donor_name')->count('donor_name'),
            'pending' => Donation::query()->where('status', 'pending')->count(),
            'pending_amount' => (float) Donation::query()->where('status', 'pending')->sum('amount'),
            'failed' => Donation::query()->whereIn('status', ['failed', 'cancelled'])->count(),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'donor_name' => ['required', 'string', 'max:150'],
            'donor_email' => ['nullable', 'email', 'max:190'],
            'donor_phone' => ['nullable', 'string', 'max:20'],
            'donor_pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'amount' => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'method' => ['required', 'in:' . implode(',', array_keys(Donation::METHODS))],
            'status' => ['required', 'in:' . implode(',', array_keys(Donation::STATUSES))],
            'transaction_ref' => ['nullable', 'string', 'max:120'],
            'donated_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['donor_pan'] = isset($data['donor_pan']) ? strtoupper($data['donor_pan']) : null;
        $data['donated_at'] = $data['donated_at'] ?? null;

        return $data;
    }
}
