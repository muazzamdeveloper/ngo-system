<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Payment gateway credentials (Razorpay, Cashfree, PhonePe). Secrets are stored encrypted and never
 * shown again after saving. Live card/UPI checkout is not wired yet; the donation request form uses
 * UPI and bank transfer, which are confirmed by hand in the donations ledger.
 */
class GatewayController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('gateways.manage');

        return view('admin.gateways.edit', [
            'razorpay' => [
                'key_id' => SiteSetting::get('gateway.razorpay.key_id'),
                'has_secret' => filled(SiteSetting::get('gateway.razorpay.key_secret')),
                'has_webhook' => filled(SiteSetting::get('gateway.razorpay.webhook_secret')),
                'enabled' => SiteSetting::get('gateway.razorpay.enabled') === '1',
            ],
            'cashfree' => [
                'app_id' => SiteSetting::get('gateway.cashfree.app_id'),
                'has_secret' => filled(SiteSetting::get('gateway.cashfree.secret_key')),
                'environment' => SiteSetting::get('gateway.cashfree.environment', 'sandbox'),
                'enabled' => SiteSetting::get('gateway.cashfree.enabled') === '1',
            ],
            'phonepe' => [
                'merchant_id' => SiteSetting::get('gateway.phonepe.merchant_id'),
                'has_salt' => filled(SiteSetting::get('gateway.phonepe.salt_key')),
                'salt_index' => SiteSetting::get('gateway.phonepe.salt_index', '1'),
                'environment' => SiteSetting::get('gateway.phonepe.environment', 'uat'),
                'enabled' => SiteSetting::get('gateway.phonepe.enabled') === '1',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('gateways.manage');

        $data = $request->validate([
            'razorpay_key_id' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_]*$/'],
            'razorpay_key_secret' => ['nullable', 'string', 'max:255'],
            'razorpay_webhook_secret' => ['nullable', 'string', 'max:255'],
            'razorpay_enabled' => ['nullable', 'boolean'],
            'cashfree_app_id' => ['nullable', 'string', 'max:120'],
            'cashfree_secret_key' => ['nullable', 'string', 'max:255'],
            'cashfree_environment' => ['required', 'in:sandbox,production'],
            'cashfree_enabled' => ['nullable', 'boolean'],
            'phonepe_merchant_id' => ['nullable', 'string', 'max:120'],
            'phonepe_salt_key' => ['nullable', 'string', 'max:255'],
            'phonepe_salt_index' => ['required', 'integer', 'between:1,99'],
            'phonepe_environment' => ['required', 'in:uat,production'],
            'phonepe_enabled' => ['nullable', 'boolean'],
        ]);

        SiteSetting::put('gateway.razorpay.key_id', $data['razorpay_key_id'] ?? null);
        SiteSetting::putSecretIfFilled('gateway.razorpay.key_secret', $data['razorpay_key_secret'] ?? null);
        SiteSetting::putSecretIfFilled('gateway.razorpay.webhook_secret', $data['razorpay_webhook_secret'] ?? null);
        SiteSetting::put('gateway.razorpay.enabled', $request->boolean('razorpay_enabled') ? '1' : '0');

        SiteSetting::put('gateway.cashfree.app_id', $data['cashfree_app_id'] ?? null);
        SiteSetting::putSecretIfFilled('gateway.cashfree.secret_key', $data['cashfree_secret_key'] ?? null);
        SiteSetting::put('gateway.cashfree.environment', $data['cashfree_environment']);
        SiteSetting::put('gateway.cashfree.enabled', $request->boolean('cashfree_enabled') ? '1' : '0');

        SiteSetting::put('gateway.phonepe.merchant_id', $data['phonepe_merchant_id'] ?? null);
        SiteSetting::putSecretIfFilled('gateway.phonepe.salt_key', $data['phonepe_salt_key'] ?? null);
        SiteSetting::put('gateway.phonepe.salt_index', (string) $data['phonepe_salt_index']);
        SiteSetting::put('gateway.phonepe.environment', $data['phonepe_environment']);
        SiteSetting::put('gateway.phonepe.enabled', $request->boolean('phonepe_enabled') ? '1' : '0');

        return back()->with('success', 'Payment gateway settings saved. Secrets are stored encrypted.');
    }
}
