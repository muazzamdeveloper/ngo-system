@php
    $org = \App\Models\OrganizationSetting::current();
    $navGroups = \App\Support\AdminNav::build(auth()->user());
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') | {{ $org->displayTitle() }}</title>
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-modules.css') }}">
    @include('partials.branding-head')
</head>
<body class="admin-body">
    <div class="admin-layout">

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                @include('partials.brand-mark')
            </div>

            <nav class="sidebar-nav" aria-label="Admin navigation">
                <a href="{{ route('dashboard') }}" class="nav-link nav-single {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <x-admin-icon name="grid" /> <span>Dashboard overview</span>
                </a>

                @foreach ($navGroups as $group)
                    <div class="nav-group {{ $group['open'] ? 'is-open' : '' }}">
                        <button type="button" class="nav-group-toggle" aria-expanded="{{ $group['open'] ? 'true' : 'false' }}">
                            <span class="nav-group-label"><x-admin-icon :name="$group['icon']" /> <span>{{ $group['label'] }}</span></span>
                            <x-admin-icon name="chevron" class="nav-chevron" />
                        </button>
                        <div class="nav-group-items">
                            @foreach ($group['items'] as $item)
                                <a href="{{ $item['href'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}">
                                    {{ $item['label'] }}
                                    @if ($item['badge'] > 0)
                                        <span class="nav-count" data-nav-count="{{ $item['badge_key'] }}">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <form method="POST" action="{{ route('logout') }}" class="sidebar-logout">
                    @csrf
                    <button type="submit"><x-admin-icon name="logout" /> Logout session</button>
                </form>
            </nav>
        </aside>

        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <div class="admin-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="menu-toggle" id="menu-toggle" aria-label="Toggle menu">&#9776;</button>
                    <span class="topbar-title">@yield('title', 'Admin')</span>
                </div>

                <div class="topbar-user">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="topbar-live">
                        <x-admin-icon name="external" /> <span>Live Site</span>
                    </a>

                    <div class="user-menu" id="user-menu">
                        <button type="button" class="user-menu-toggle" aria-haspopup="true" aria-expanded="false">
                            <span class="user-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="topbar-name">{{ auth()->user()->name }}</span>
                            <x-admin-icon name="chevron" class="nav-chevron" />
                        </button>
                        <div class="user-menu-panel">
                            <a href="{{ route('profile.edit') }}"><x-admin-icon name="user" /> My profile</a>
                            <a href="{{ route('password.edit') }}"><x-admin-icon name="lock" /> Change password</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"><x-admin-icon name="logout" /> Log out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                @if (session('success'))
                    <div class="alert alert-success" role="status">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                @endif

                @yield('content')
            </main>
        </div>

    </div>

    <script src="{{ asset('js/admin.js') }}"></script>
    @stack('scripts')
</body>
</html>
