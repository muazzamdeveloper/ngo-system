@extends('layouts.admin')
@section('title', 'Site configuration')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Site configuration</h1>
            <p class="page-subtitle">Search engine details, web app, homepage popup, tracking codes and map.</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-light">Preview live website</a>
    </div>

    <form method="POST" action="{{ route('site-config.update') }}" enctype="multipart/form-data" class="site-config">
        @csrf @method('PUT')

        <div class="config-grid">
            <section class="card form-card">
                <h2 class="section-title">General &amp; SEO</h2>
                <div class="form-group"><label class="form-label" for="seo_title">Meta title</label>
                    <input class="form-input" id="seo_title" name="seo[title]" maxlength="255" value="{{ old('seo.title', $values['seo.title'] ?? '') }}"></div>
                <div class="form-group"><label class="form-label" for="seo_description">Meta description</label>
                    <textarea class="form-input" id="seo_description" name="seo[description]" rows="3" maxlength="500">{{ old('seo.description', $values['seo.description'] ?? '') }}</textarea></div>
                <div class="form-group"><label class="form-label" for="seo_keywords">Meta keywords</label>
                    <textarea class="form-input" id="seo_keywords" name="seo[keywords]" rows="2" maxlength="500">{{ old('seo.keywords', $values['seo.keywords'] ?? '') }}</textarea>
                    <div class="form-hint">Comma separated.</div></div>
                <div class="form-group"><label class="form-label" for="seo_author">Meta author</label>
                    <input class="form-input" id="seo_author" name="seo[author]" maxlength="150" value="{{ old('seo.author', $values['seo.author'] ?? '') }}"></div>
                <div class="form-group"><label class="form-label" for="map_url">Google Map embed URL</label>
                    <textarea class="form-input" id="map_url" name="map[embed_url]" rows="2" maxlength="1000" placeholder="https://www.google.com/maps/embed?pb=…">{{ old('map.embed_url', $values['map.embed_url'] ?? '') }}</textarea>
                    <div class="form-hint">Paste the src URL from Google Maps “Embed a map”. Shown on the contact section.</div></div>
            </section>

            <section class="card form-card">
                <h2 class="section-title">Progressive web app</h2>
                <label class="switch-row"><input type="checkbox" name="pwa[enabled]" value="1" @checked(($values['pwa.enabled'] ?? '0') === '1')> <span>Enable web app install (manifest)</span></label>
                <div class="form-group"><label class="form-label" for="pwa_name">App name</label>
                    <input class="form-input" id="pwa_name" name="pwa[name]" maxlength="80" value="{{ old('pwa.name', $values['pwa.name'] ?? '') }}"></div>
                <div class="form-group"><label class="form-label" for="pwa_short">Short name</label>
                    <input class="form-input" id="pwa_short" name="pwa[short_name]" maxlength="30" value="{{ old('pwa.short_name', $values['pwa.short_name'] ?? '') }}"></div>
                <div class="form-grid">
                    <div class="form-group"><label class="form-label" for="pwa_theme">Theme colour</label>
                        <input class="form-input" id="pwa_theme" name="pwa[theme_color]" maxlength="7" placeholder="#1e3a8a" value="{{ old('pwa.theme_color', $values['pwa.theme_color'] ?? '') }}"></div>
                    <div class="form-group"><label class="form-label" for="pwa_bg">Background colour</label>
                        <input class="form-input" id="pwa_bg" name="pwa[background_color]" maxlength="7" placeholder="#ffffff" value="{{ old('pwa.background_color', $values['pwa.background_color'] ?? '') }}"></div>
                    <div class="form-group"><label class="form-label" for="pwa_display">Display mode</label>
                        <select class="form-input" id="pwa_display" name="pwa[display]">
                            @foreach (['standalone', 'fullscreen', 'minimal-ui', 'browser'] as $mode)
                                <option value="{{ $mode }}" @selected(($values['pwa.display'] ?? 'standalone') === $mode)>{{ ucfirst($mode) }}</option>
                            @endforeach
                        </select></div>
                </div>
                <div class="form-group"><label class="form-label" for="pwa_desc">App description</label>
                    <textarea class="form-input" id="pwa_desc" name="pwa[description]" rows="2" maxlength="300">{{ old('pwa.description', $values['pwa.description'] ?? '') }}</textarea></div>
                <div class="form-group"><label class="form-label" for="pwa_icon">App icon (PNG, at least 192×192)</label>
                    @if ($pwaIcon)<div class="current-file"><img src="{{ $pwaIcon }}" alt="Current app icon" class="thumb-large"></div>@endif
                    <input class="form-input file-input" id="pwa_icon" type="file" name="pwa[icon]" accept="image/png"></div>
            </section>

            <section class="card form-card">
                <h2 class="section-title">Homepage popup &amp; announcement</h2>
                <label class="switch-row"><input type="checkbox" name="popup[enabled]" value="1" @checked(($values['popup.enabled'] ?? '0') === '1')> <span>Show popup to visitors</span></label>
                <div class="form-group"><label class="form-label" for="popup_title">Headline</label>
                    <input class="form-input" id="popup_title" name="popup[title]" maxlength="150" value="{{ old('popup.title', $values['popup.title'] ?? '') }}"></div>
                <div class="form-group"><label class="form-label" for="popup_msg">Message</label>
                    <textarea class="form-input" id="popup_msg" name="popup[message]" rows="3" maxlength="1000">{{ old('popup.message', $values['popup.message'] ?? '') }}</textarea></div>
                <div class="form-grid">
                    <div class="form-group"><label class="form-label" for="popup_btn">Button text</label>
                        <input class="form-input" id="popup_btn" name="popup[button_text]" maxlength="40" value="{{ old('popup.button_text', $values['popup.button_text'] ?? '') }}"></div>
                    <div class="form-group"><label class="form-label" for="popup_url">Button link</label>
                        <input class="form-input" id="popup_url" name="popup[button_url]" maxlength="500" placeholder="/donate or https://…" value="{{ old('popup.button_url', $values['popup.button_url'] ?? '') }}"></div>
                </div>
                <div class="form-group"><label class="form-label" for="popup_img">Banner image (optional)</label>
                    @if ($popupImage)<div class="current-file"><img src="{{ $popupImage }}" alt="Current banner" class="thumb-large"></div>@endif
                    <input class="form-input file-input" id="popup_img" type="file" name="popup[image]" accept="image/png,image/jpeg,image/webp"></div>
            </section>

            <section class="card form-card">
                <h2 class="section-title">SEO &amp; tracking</h2>
                <div class="form-group"><label class="form-label" for="ga">Google Analytics code</label>
                    <textarea class="form-input code-input" id="ga" name="tracking[ga]" @disabled(! auth()->user()->isSuperAdmin()) rows="4" placeholder="<script>…</script>">{{ old('tracking.ga', $values['tracking.ga'] ?? '') }}</textarea>
                    <div class="form-hint">Complete script tags. Added to the page head.</div></div>
                <div class="form-group"><label class="form-label" for="fb">Facebook Pixel code</label>
                    <textarea class="form-input code-input" id="fb" name="tracking[fb_pixel]" @disabled(! auth()->user()->isSuperAdmin()) rows="4" placeholder="<script>…</script>">{{ old('tracking.fb_pixel', $values['tracking.fb_pixel'] ?? '') }}</textarea></div>
                <div class="form-group"><label class="form-label" for="gsc">Google Search Console verification tag</label>
                    <input class="form-input code-input" id="gsc" name="tracking[gsc_meta]" @disabled(! auth()->user()->isSuperAdmin()) maxlength="500" placeholder='<meta name="google-site-verification" content="…" />' value="{{ old('tracking.gsc_meta', $values['tracking.gsc_meta'] ?? '') }}"></div>
            </section>
        </div>

        <div class="savebar">
            <span>Changes apply across the public website as soon as you save.</span>
            <button type="submit" class="btn">Save all site configuration</button>
        </div>
    </form>
@endsection
