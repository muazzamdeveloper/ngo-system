@php($org = \App\Models\OrganizationSetting::current())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Welcome') | {{ $org->displayTitle() }}</title>
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    @include('partials.branding-head')
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="auth-brand">
            @if ($org->logoUrl())
                <img class="brand-logo-large" src="{{ $org->logoUrl() }}" alt="">
            @endif
            <h1>{{ $org->name }}</h1>
            @if (filled($org->tagline))
                <p class="brand-tagline">{{ $org->tagline }}</p>
            @endif
        </div>

        @yield('content')
    </main>
</body>
</html>