{{-- Public header: top strip (social, news ticker, phone), brand bar with Donate, and the main menu. --}}
@php
    use App\Support\SiteNav;
    use App\Models\SiteSetting;
    $menu = config('site_nav.menu', []);
    $tickerLabel = filled($org->ticker_label) ? $org->ticker_label : 'News & Updates';
@endphp

<div class="chrome-topbar">
    <div class="chrome-topbar-inner">
        <div class="chrome-social">
            @include('partials.social-icons')
        </div>

        @if ($chrome['ticker']->isNotEmpty())
            <div class="chrome-ticker" role="region" aria-label="{{ $tickerLabel }}">
                <span class="chrome-ticker-label"><x-icon name="bolt" /> {{ $tickerLabel }}</span>
                <div class="ticker-window">
                    <div class="ticker-move" style="--ticker-seconds: {{ max(24, $chrome['ticker']->count() * 9) }}s">
                        @foreach ([1, 2] as $copy)
                            <ul class="ticker-list" @if ($copy === 2) aria-hidden="true" @endif>
                                @foreach ($chrome['ticker'] as $item)
                                    <li>
                                        @if ($item->url)
                                            <a href="{{ $item->url }}" @if (str_starts_with($item->url, 'http')) target="_blank" rel="noopener" @endif @if ($copy === 2) tabindex="-1" @endif>{{ $item->text }}</a>
                                        @else
                                            <span>{{ $item->text }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if ($org->phone)
            <a class="chrome-phone" href="{{ $org->phoneHref() }}"><x-icon name="phone" /> {{ $org->phone }}</a>
        @endif
    </div>
</div>

<header class="chrome-brandbar">
    <div class="chrome-brandbar-inner">
        <a class="chrome-brand" href="{{ url('/') }}">
            @if ($org->logoUrl())
                <img class="chrome-logo" src="{{ $org->logoUrl() }}" alt="{{ $org->name }} logo">
            @else
                <span class="chrome-logo chrome-logo-initials">{{ $org->initials() }}</span>
            @endif
            <span class="chrome-brand-name">{{ $org->name }}</span>
        </a>

        <div class="chrome-brand-actions">
            <div class="chrome-lang" aria-label="Select language"><div id="google_translate_element"><span class="chrome-lang-fallback">Select Language</span></div></div>
            @if (Route::has('donate'))
                <a class="chrome-donate" href="{{ route('donate') }}"><x-icon name="heart" /> Donate Us</a>
            @endif
        </div>
    </div>
</header>

<nav class="chrome-menu" aria-label="Main menu">
    <div class="chrome-menu-inner">
        <button type="button" class="chrome-menu-toggle" aria-expanded="false" aria-controls="chrome-menu-list">
            <span></span><span></span><span></span><span class="sr-only">Menu</span>
        </button>

        <ul id="chrome-menu-list" class="chrome-menu-list">
            @foreach ($menu as $item)
                @php($children = $item['children'] ?? [])
                @if ($children === [])
                    @php($link = SiteNav::link($item))
                    <li>
                        <a href="{{ $link['href'] }}" @class(['is-current' => $link['active']])>
                            <x-icon name="{{ $item['icon'] ?? 'chevron' }}" /> {{ strtoupper($item['label']) }}
                        </a>
                    </li>
                @else
                    <li class="has-dropdown @if (SiteNav::anyChildActive($children)) is-current @endif">
                        <button type="button" class="dropdown-toggle" aria-expanded="false">
                            <x-icon name="{{ $item['icon'] ?? 'chevron' }}" /> {{ strtoupper($item['label']) }}
                            <x-icon name="chevron-down" class="chevron" />
                        </button>
                        <ul class="dropdown">
                            @foreach ($children as $child)
                                @php($childLink = SiteNav::link($child))
                                <li><a href="{{ $childLink['href'] }}" @class(['is-current' => $childLink['active']])>{{ $child['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</nav>
