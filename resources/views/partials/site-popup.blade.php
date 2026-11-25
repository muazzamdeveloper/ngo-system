{{-- Homepage popup from Site configuration. Closing it remembers the choice in this browser. --}}
@php
    use App\Models\SiteSetting;
    use App\Services\SiteMedia;
    $popupOn = SiteSetting::get('popup.enabled') === '1' && filled(SiteSetting::get('popup.title'));
    $popupKey = 'popup-' . substr(sha1((string) SiteSetting::get('popup.title') . (string) SiteSetting::get('popup.message')), 0, 10);
    $popupImage = SiteMedia::url(SiteSetting::get('popup.image_path'));
    $popupUrl = SiteSetting::get('popup.button_url');
    $popupSafeUrl = ($popupUrl && (str_starts_with($popupUrl, '/') || preg_match('#^https?://#i', $popupUrl))) ? $popupUrl : null;
@endphp
@if ($popupOn)
    <div class="site-popup" id="site-popup" data-popup-key="{{ $popupKey }}" hidden role="dialog" aria-modal="false" aria-labelledby="site-popup-title">
        <div class="site-popup-card">
            <button type="button" class="site-popup-close" data-popup-close aria-label="Close">&times;</button>
            @if ($popupImage)<img src="{{ $popupImage }}" alt="" class="site-popup-image">@endif
            <h2 id="site-popup-title">{{ SiteSetting::get('popup.title') }}</h2>
            @if (SiteSetting::get('popup.message'))<p>{{ SiteSetting::get('popup.message') }}</p>@endif
            @if ($popupSafeUrl && filled(SiteSetting::get('popup.button_text')))
                <a class="btn" href="{{ $popupSafeUrl }}">{{ SiteSetting::get('popup.button_text') }}</a>
            @endif
        </div>
    </div>
    <script>
        (function () {
            var popup = document.getElementById('site-popup');
            if (!popup) return;
            var key = popup.getAttribute('data-popup-key');
            try { if (localStorage.getItem(key)) return; } catch (e) {}
            popup.hidden = false;
            popup.querySelector('[data-popup-close]').addEventListener('click', function () {
                popup.hidden = true;
                try { localStorage.setItem(key, '1'); } catch (e) {}
            });
        })();
    </script>
@endif
