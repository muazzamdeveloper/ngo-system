{{-- Site icon and theme colours. A saved palette (Themes & layouts) overrides the organization colour. Expects $org. --}}
@php
    $themePrimary = \App\Models\SiteSetting::get('theme.primary');
    $themeAccent = \App\Models\SiteSetting::get('theme.accent');
    $primary = ($themePrimary && preg_match('/^#[0-9A-Fa-f]{6}$/', $themePrimary)) ? $themePrimary : $org->primaryColor();
    $accent = ($themeAccent && preg_match('/^#[0-9A-Fa-f]{6}$/', $themeAccent)) ? $themeAccent : '#ff6f59';
@endphp
@if ($org->faviconUrl())
    <link rel="icon" href="{{ $org->faviconUrl() }}">
@endif
<style>:root { --color-primary: {{ $primary }}; --color-primary-dark: {{ $themePrimary ? $primary : $org->primaryDark() }}; --color-accent: {{ $accent }}; }</style>
