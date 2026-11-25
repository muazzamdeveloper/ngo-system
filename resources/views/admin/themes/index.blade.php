@extends('layouts.admin')
@section('title', 'Themes & layouts')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Website themes &amp; colours</h1>
            <p class="page-subtitle">Choose a palette, or enter your own brand colours. They apply to the whole public website.</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-light">View live website</a>
    </div>

    <div class="card form-card custom-colours">
        <div>
            <h2 class="section-title">Custom brand colours</h2>
            <p class="muted small">Match your logo colours. Updates headers, badges, buttons and accents.</p>
        </div>
        <form method="POST" action="{{ route('themes.custom') }}" class="colour-form">
            @csrf
            <label class="colour-field">Primary (headers &amp; brand)
                <span class="colour-input"><input type="color" name="primary" value="{{ $primary ?: '#005b60' }}" aria-label="Primary colour"><code>{{ $primary ?: 'default' }}</code></span></label>
            <label class="colour-field">Accent (buttons &amp; highlights)
                <span class="colour-input"><input type="color" name="accent" value="{{ $accent ?: '#ff6f59' }}" aria-label="Accent colour"><code>{{ $accent ?: 'default' }}</code></span></label>
            <button type="submit" class="btn">Save colours</button>
        </form>
    </div>

    <h2 class="section-title">Preset palettes</h2>
    <div class="palette-grid">
        @foreach ($palettes as $key => [$name, $description, $primaryHex, $accentHex])
            <div class="card palette-card {{ $current === $key ? 'is-current' : '' }}">
                <div class="card-head">
                    <h3 class="card-title">{{ $name }}</h3>
                    @if ($current === $key)<span class="status-badge status-active">Active</span>@endif
                </div>
                <p class="muted small">{{ $description }}</p>
                <div class="swatches">
                    <span class="swatch" style="background: {{ $primaryHex }}" title="Primary {{ $primaryHex }}"></span>
                    <span class="swatch" style="background: {{ $accentHex }}" title="Accent {{ $accentHex }}"></span>
                    <span class="header-preview" style="background: {{ $primaryHex }}">HEADER <span class="donate-preview" style="background: {{ $accentHex }}">DONATE</span></span>
                </div>
                <form method="POST" action="{{ route('themes.apply') }}">
                    @csrf
                    <input type="hidden" name="palette" value="{{ $key }}">
                    <button type="submit" class="btn btn-light btn-block" @disabled($current === $key)>{{ $current === $key ? 'Current palette' : 'Apply palette' }}</button>
                </form>
            </div>
        @endforeach
    </div>
@endsection
