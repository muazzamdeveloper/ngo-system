<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\PaymentSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public donation request. A visitor who pays by UPI or bank transfer leaves their details here;
 * the donation stays pending until the team confirms the payment in the donations ledger.
 */
class DonateController extends Controller
{
    public function create(Request $request): View
    {
        return view('public.donate', [
            'campaigns' => Campaign::query()->where('status', 'active')->orderBy('title')->get(['id', 'title']),
            'payment' => PaymentSetting::current(),
            'selected' => $request->query('campaign'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Hidden field trap: bots fill it in, real visitors never see it.
        if (filled($request->input('website'))) {
            return redirect()->route('donate.thanks');
        }

        $data = $request->validate([
            'donor_name' => ['required', 'string', 'max:150'],
            'donor_email' => ['nullable', 'email', 'max:190'],
            'donor_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{7,20}$/'],
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'method' => ['required', 'in:upi,bank'],
            'transaction_ref' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        Donation::create([
            'campaign_id' => $data['campaign_id'] ?? null,
            'donor_name' => $data['donor_name'],
            'donor_email' => $data['donor_email'] ?? null,
            'donor_phone' => $data['donor_phone'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'status' => 'pending',
            'source' => 'public',
            'transaction_ref' => $data['transaction_ref'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('donate.thanks');
    }

    public function thanks(): View
    {
        return view('public.donate-thanks');
    }
}
