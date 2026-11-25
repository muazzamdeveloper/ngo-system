@php($org = \App\Models\OrganizationSetting::current())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'My account') | {{ $org->displayTitle() }}</title>
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @include('partials.branding-head')
</head>
<body>
    <header class="member-header">
        <div class="member-header-inner">
            <span class="sidebar-brand member-brand">
                @include('partials.brand-mark', ['badgeClass' => 'brand-badge-dark'])
            </span>

            <div class="topbar-user">
                <span class="topbar-name">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-small">Log out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="member-container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
