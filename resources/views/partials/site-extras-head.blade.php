{{-- SEO, web app manifest and tracking codes from Site configuration. Only admin-entered values are printed. --}}
@php
    use App\Models\SiteSetting;
    $seoDescription = SiteSetting::get('seo.description');
    $seoKeywords = SiteSetting::get('seo.keywords');
    $seoAuthor = SiteSetting::get('seo.author');
    $pwaOn = SiteSetting::get('pwa.enabled') === '1';
@endphp
@if ($seoDescription)<meta name="description" content="{{ $seoDescription }}">@endif
@if ($seoKeywords)<meta name="keywords" content="{{ $seoKeywords }}">@endif
@if ($seoAuthor)<meta name="author" content="{{ $seoAuthor }}">@endif
@if (SiteSetting::get('seo.title'))<meta property="og:title" content="{{ SiteSetting::get('seo.title') }}">@endif
@if ($seoDescription)<meta property="og:description" content="{{ $seoDescription }}">@endif
@if ($pwaOn)
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="{{ SiteSetting::get('pwa.theme_color') ?: '#1e3a8a' }}">
@endif
@if (SiteSetting::get('tracking.gsc_meta'))
    {!! SiteSetting::get('tracking.gsc_meta') !!}
@endif
@if (SiteSetting::get('tracking.ga'))
    {!! SiteSetting::get('tracking.ga') !!}
@endif
@if (SiteSetting::get('tracking.fb_pixel'))
    {!! SiteSetting::get('tracking.fb_pixel') !!}
@endif
