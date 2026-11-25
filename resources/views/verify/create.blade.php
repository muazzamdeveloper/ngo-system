@extends('layouts.site')

@section('title', 'Verify membership')

@section('hero')
    <span class="hero-pill">Member verification</span>
    <h1>Verify &amp; Download</h1>
    <p class="hero-text">Enter your Aadhaar number to confirm your membership and download your ID card and joining letter.</p>
    <ul class="hero-chips">
        <li><x-icon name="shield" /> Your Aadhaar number is never shown on screen</li>
        <li><x-icon name="check" /> Download links work for {{ \App\Http\Controllers\VerifyController::LINK_MINUTES }} minutes</li>
    </ul>
@endsection

@section('content')
    <section class="site-card">
        <div class="section-head">
            <span class="section-icon"><x-icon name="id" /></span>
            <div>
                <h2>Enter your Aadhaar number</h2>
                <p>Use the Aadhaar number you applied with.</p>
            </div>
        </div>

        @if ($errors->has('form'))
            <div class="alert alert-danger">{{ $errors->first('form') }}</div>
        @endif

        <form method="POST" action="{{ route('verify.check') }}" autocomplete="off" class="verify-row">
            @csrf
            <input class="form-input" type="text" id="aadhaar" name="aadhaar" inputmode="numeric" maxlength="14"
                   placeholder="12-digit Aadhaar number" autocomplete="off" required aria-label="Aadhaar number">
            <button type="submit" class="btn">Verify</button>
        </form>
        @error('aadhaar') <div class="form-error">{{ $message }}</div> @enderror
    </section>

    @include('verify._documents', ['links' => null])

    <p class="site-link-line">Not applied yet? <a href="{{ route('apply.create') }}">Apply for membership</a></p>
@endsection
