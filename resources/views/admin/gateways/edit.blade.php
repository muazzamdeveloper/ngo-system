@extends('layouts.admin')
@section('title', 'Payment gateways')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Payment gateways</h1>
            <p class="page-subtitle">Your own merchant accounts. Secrets are encrypted and never shown again after saving.</p>
        </div>
    </div>
    <div class="notice notice-info">Live card and UPI checkout is not connected yet. Donations from the website are recorded as pending, and the team confirms the payment in the donations ledger.</div>

    <form method="POST" action="{{ route('gateways.update') }}" class="gateway-grid">
        @csrf @method('PUT')

        <section class="card form-card">
            <div class="card-head"><h2 class="section-title">Razorpay</h2>
                <span class="status-badge status-{{ $razorpay['enabled'] ? 'active' : 'inactive' }}">{{ $razorpay['enabled'] ? 'Enabled' : 'Disabled' }}</span></div>
            <div class="form-group"><label class="form-label" for="rz_key">Key ID</label>
                <input class="form-input" id="rz_key" name="razorpay_key_id" value="{{ old('razorpay_key_id', $razorpay['key_id']) }}" placeholder="rzp_live_…" autocomplete="off"></div>
            <div class="form-group"><label class="form-label" for="rz_secret">Key secret</label>
                <input class="form-input" id="rz_secret" type="password" name="razorpay_key_secret" autocomplete="new-password" placeholder="{{ $razorpay['has_secret'] ? 'Saved — leave blank to keep' : 'Enter key secret' }}"></div>
            <div class="form-group"><label class="form-label" for="rz_webhook">Webhook secret (optional)</label>
                <input class="form-input" id="rz_webhook" type="password" name="razorpay_webhook_secret" autocomplete="new-password" placeholder="{{ $razorpay['has_webhook'] ? 'Saved — leave blank to keep' : '' }}"></div>
            <label class="switch-row"><input type="checkbox" name="razorpay_enabled" value="1" @checked($razorpay['enabled'])> <span>Enable Razorpay on the donation page</span></label>
        </section>

        <section class="card form-card">
            <div class="card-head"><h2 class="section-title">Cashfree</h2>
                <span class="status-badge status-{{ $cashfree['enabled'] ? 'active' : 'inactive' }}">{{ $cashfree['enabled'] ? 'Enabled' : 'Disabled' }}</span></div>
            <div class="form-group"><label class="form-label" for="cf_app">App ID / client ID</label>
                <input class="form-input" id="cf_app" name="cashfree_app_id" value="{{ old('cashfree_app_id', $cashfree['app_id']) }}" autocomplete="off"></div>
            <div class="form-group"><label class="form-label" for="cf_secret">Secret key</label>
                <input class="form-input" id="cf_secret" type="password" name="cashfree_secret_key" autocomplete="new-password" placeholder="{{ $cashfree['has_secret'] ? 'Saved — leave blank to keep' : 'Enter secret key' }}"></div>
            <div class="form-group"><label class="form-label" for="cf_env">Environment</label>
                <select class="form-input" id="cf_env" name="cashfree_environment">
                    <option value="sandbox" @selected($cashfree['environment'] === 'sandbox')>Sandbox (test)</option>
                    <option value="production" @selected($cashfree['environment'] === 'production')>Production</option>
                </select></div>
            <label class="switch-row"><input type="checkbox" name="cashfree_enabled" value="1" @checked($cashfree['enabled'])> <span>Enable Cashfree on the donation page</span></label>
        </section>

        <section class="card form-card">
            <div class="card-head"><h2 class="section-title">PhonePe standard checkout</h2>
                <span class="status-badge status-{{ $phonepe['enabled'] ? 'active' : 'inactive' }}">{{ $phonepe['enabled'] ? 'Enabled' : 'Disabled' }}</span></div>
            <div class="form-group"><label class="form-label" for="pp_merchant">Merchant ID</label>
                <input class="form-input" id="pp_merchant" name="phonepe_merchant_id" value="{{ old('phonepe_merchant_id', $phonepe['merchant_id']) }}" autocomplete="off"></div>
            <div class="form-group"><label class="form-label" for="pp_salt">Salt key</label>
                <input class="form-input" id="pp_salt" type="password" name="phonepe_salt_key" autocomplete="new-password" placeholder="{{ $phonepe['has_salt'] ? 'Saved — leave blank to keep' : 'Enter salt key' }}"></div>
            <div class="form-grid">
                <div class="form-group"><label class="form-label" for="pp_index">Salt index</label>
                    <input class="form-input" id="pp_index" type="number" min="1" max="99" name="phonepe_salt_index" value="{{ old('phonepe_salt_index', $phonepe['salt_index']) }}" required></div>
                <div class="form-group"><label class="form-label" for="pp_env">Environment</label>
                    <select class="form-input" id="pp_env" name="phonepe_environment">
                        <option value="uat" @selected($phonepe['environment'] === 'uat')>UAT / sandbox</option>
                        <option value="production" @selected($phonepe['environment'] === 'production')>Production</option>
                    </select></div>
            </div>
            <label class="switch-row"><input type="checkbox" name="phonepe_enabled" value="1" @checked($phonepe['enabled'])> <span>Enable PhonePe on the donation page</span></label>
        </section>

        <div class="savebar span-all">
            <span>Donation QR and bank details are managed under Donation &amp; payment settings.</span>
            <button type="submit" class="btn">Save gateway settings</button>
        </div>
    </form>
@endsection
