@php($org = \App\Models\OrganizationSetting::current())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Welcome') | {{ $org->displayTitle() }}</title>
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/public-modules.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site-chrome.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home-v2.css') }}">
    @include('partials.branding-head')
    @include('partials.site-extras-head')
</head>
<body class="site-page">
    @include('partials.site-header')

    @hasSection('hero')
        <section class="site-hero">
            <div class="site-hero-inner">
                @yield('hero')
            </div>
        </section>
    @endif

    <main class="site-main @yield('main_class')">
        @yield('content')
    </main>

    @include('partials.site-footer')

    @include('partials.site-popup')
    <script src="{{ asset('js/apply.js') }}" defer></script>
    <script src="{{ asset('js/site.js') }}" defer></script>
    <script src="{{ asset('js/site-chrome.js') }}" defer></script>
</body>
</html>
