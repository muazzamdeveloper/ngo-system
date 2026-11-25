@extends('layouts.site')

@section('title', 'Membership verified')

@section('hero')
    <span class="hero-pill">Member verification</span>
    <h1>Verify &amp; Download</h1>
    <p class="hero-text">Your membership is confirmed. Download your documents below.</p>
@endsection

@section('content')
    <section class="site-card">
        <div class="verify-ok">
            <span class="section-icon"><x-icon name="check" /></span>
            <div>
                <h2>Membership verified</h2>
                <p class="text-muted" style="margin: 0.15rem 0 0;">Your Aadhaar number is not shown here, for your privacy.</p>
            </div>
        </div>
    </section>

    @include('verify._documents')

    <p class="site-link-line"><a href="{{ route('verify.create') }}">Verify another number</a></p>
@endsection
