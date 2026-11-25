@extends('layouts.admin')
@section('title', 'Domains & SSL')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Domains &amp; SSL routing</h1>
            <p class="page-subtitle">Connect your own domain name, or use the free subdomain.</p>
        </div>
    </div>

    <div class="card form-card">
        <h2 class="section-title">Current public address</h2>
        @if ($domain && $verifiedAt)
            <p class="big-link">https://{{ $domain }}</p>
            <p class="small">Connected {{ \Illuminate\Support\Carbon::parse($verifiedAt)->format('d M Y, h:i A') }}. HTTPS certificates are issued and renewed by your hosting provider.</p>
            <form method="POST" action="{{ route('domains.disconnect') }}">@csrf<button class="btn btn-light">Disconnect custom domain</button></form>
        @else
            <p class="big-link">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'Free subdomain' }}</p>
            <p class="small muted">No custom domain is connected yet.</p>
        @endif
    </div>

    <div class="card form-card">
        <h2 class="section-title">Bring your own domain</h2>
        <p>Step 1. At your domain registrar, add this CNAME record:</p>
        <table class="table dns-table">
            <thead><tr><th>Type</th><th>Host / name</th><th>Points to</th><th>TTL</th></tr></thead>
            <tbody><tr><td><span class="tag">CNAME</span></td><td><code>www</code></td><td><code>{{ $target }}</code></td><td>Automatic</td></tr></tbody>
        </table>
        <p>Step 2. Enter your domain and verify the connection. DNS changes can take 5 to 30 minutes.</p>
        <form method="POST" action="{{ route('domains.verify') }}" class="toolbar">
            @csrf
            <input class="form-input toolbar-search" name="domain" placeholder="www.example.org" value="{{ old('domain') }}" required aria-label="Domain name">
            <button type="submit" class="btn">Verify &amp; connect</button>
        </form>
    </div>
@endsection
