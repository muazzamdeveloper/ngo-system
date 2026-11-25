@extends('layouts.site')

@section('title', 'Application received')

@section('hero')
    <span class="hero-pill">Application received</span>
    <h1>Thank you</h1>
    <p class="hero-text">Your application has been sent and is waiting for review.</p>
@endsection

@section('content')
    <section class="site-card thanks-box">
        <div class="section-head" style="justify-content: center;">
            <span class="section-icon"><x-icon name="check" /></span>
            <div style="text-align: left;">
                <h2>Application received</h2>
                <p>We will contact you on the mobile number or email you gave. You do not need to apply again.</p>
            </div>
        </div>

        <p class="text-muted">Once the admin approves your application you can download your ID card and joining letter with your Aadhaar number.</p>
        <p><a class="btn" href="{{ route('verify.create') }}">Check your application status</a></p>
    </section>
@endsection
