<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Custom domain (bring your own domain). The owner adds a CNAME record at their registrar,
 * then we check the DNS record before saving the domain as connected.
 */
class DomainController extends Controller
{
    public const PLATFORM_TARGET = 'domains.portalwale.com';

    public function edit(): View
    {
        Gate::authorize('site.manage');

        return view('admin.domains.edit', [
            'domain' => SiteSetting::get('domain.custom'),
            'verifiedAt' => SiteSetting::get('domain.verified_at'),
            'target' => self::PLATFORM_TARGET,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        Gate::authorize('site.manage');

        $data = $request->validate([
            'domain' => ['required', 'string', 'max:253', 'regex:/^(?=.{4,253}$)(www\.)?([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i'],
        ]);

        $host = strtolower($data['domain']);
        $records = @dns_get_record($host, DNS_CNAME) ?: [];
        $pointsHere = collect($records)->contains(fn ($r) => rtrim(strtolower($r['target'] ?? ''), '.') === self::PLATFORM_TARGET);

        if (! $pointsHere) {
            return back()->withInput()->with('error', 'No CNAME record pointing to ' . self::PLATFORM_TARGET . ' was found yet. DNS changes can take 5 to 30 minutes.');
        }

        SiteSetting::put('domain.custom', $host);
        SiteSetting::put('domain.verified_at', now()->toDateTimeString());

        return back()->with('success', "{$host} is connected.");
    }

    public function disconnect(): RedirectResponse
    {
        Gate::authorize('site.manage');

        SiteSetting::put('domain.custom', null);
        SiteSetting::put('domain.verified_at', null);

        return back()->with('success', 'Custom domain disconnected. The free subdomain is used again.');
    }
}
