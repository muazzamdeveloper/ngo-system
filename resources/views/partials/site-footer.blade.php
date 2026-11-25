{{-- Public footer: four info boxes, link columns, donation QR, legal links and credit. Expects $org. --}}
@php
    use App\Support\SiteNav;
    use App\Models\SiteSetting;
    $hours = SiteSetting::get('contact.hours') ?: 'Mon – Sat: 10:00 – 18:00';
    $credit = config('site_nav.credit');
@endphp

<footer class="chrome-footer">
    <div class="chrome-footer-inner">
        <div class="chrome-info-grid">
            <div class="chrome-info-box">
                <span class="chrome-info-icon"><x-icon name="pin" /></span>
                <strong>Our Address</strong>
                <span>{{ $org->address ?: $org->address_secondary }}</span>
            </div>
            <div class="chrome-info-box">
                <span class="chrome-info-icon"><x-icon name="phone" /></span>
                <strong>Call Us</strong>
                @if ($org->phone)<a href="{{ $org->phoneHref() }}">{{ $org->phone }}</a>@endif
            </div>
            <div class="chrome-info-box">
                <span class="chrome-info-icon"><x-icon name="mail" /></span>
                <strong>Mail Us</strong>
                @if ($org->email)<a href="mailto:{{ $org->email }}">{{ $org->email }}</a>@endif
            </div>
            <div class="chrome-info-box">
                <span class="chrome-info-icon"><x-icon name="clock" /></span>
                <strong>Working Hours</strong>
                <span>{{ $hours }}</span>
            </div>
        </div>

        <div class="chrome-footer-cols">
            <div class="chrome-footer-about">
                @if ($org->logoUrl())
                    <img class="chrome-footer-logo" src="{{ $org->logoUrl() }}" alt="{{ $org->name }} logo">
                @endif
                <p>{{ $org->name }} is a registered NGO dedicated to education, health, livelihood, social welfare, and community empowerment.</p>
                <h3 class="chrome-footer-heading">Follow Us</h3>
                @include('partials.social-icons')
            </div>

            <div>
                <h3 class="chrome-footer-heading">Quick Links</h3>
                <ul class="chrome-footer-links">
                    @foreach (config('site_nav.quick_links', []) as $link)
                        @php($resolved = SiteNav::link($link))
                        <li><a href="{{ $resolved['href'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="chrome-footer-heading">Our Services</h3>
                <ul class="chrome-footer-links">
                    @foreach (config('site_nav.services', []) as $link)
                        @php($resolved = SiteNav::link($link))
                        <li><a href="{{ $resolved['href'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="chrome-footer-heading">Support &amp; Visitors</h3>
                <div class="chrome-support-card">
                    <span class="chrome-support-title"><x-icon name="qr" /> Scan to Support</span>
                    @if (\App\Models\PaymentSetting::current()->hasDonationQr())
                        <img class="chrome-support-qr" src="{{ route('donation-qr') }}" alt="Donation QR code">
                    @endif
                    @if (Route::has('donate'))
                        <a class="chrome-support-donate" href="{{ route('donate') }}"><x-icon name="heart" /> Donate Now</a>
                    @endif
                    @if ($org->phone)<span class="chrome-support-phone">{{ $org->phone }}</span>@endif
                </div>
            </div>
        </div>

        <div class="chrome-legal-bar">
            <span>&copy; {{ now()->year }} <strong>{{ $org->name }}</strong>. All rights reserved.</span>
            <nav aria-label="Legal">
                @foreach (config('site_nav.legal', []) as $link)
                    @php($resolved = SiteNav::link($link))
                    <a href="{{ $resolved['href'] }}">{{ $link['label'] }}</a>
                @endforeach
            </nav>
        </div>
        <p class="chrome-credit">
            {{ $credit['text'] ?? '' }}
            @if (! empty($credit['url']))<a href="{{ $credit['url'] }}" target="_blank" rel="noopener">{{ $credit['name'] }}</a>@else<strong>{{ $credit['name'] ?? '' }}</strong>@endif
        </p>
    </div>
</footer>

{{-- Floating buttons: call, WhatsApp, support, donate. --}}
<div class="chrome-float" aria-label="Quick contact">
    @if ($org->phone)<a class="chrome-float-btn chrome-float-call" href="{{ $org->phoneHref() }}" aria-label="Call us"><x-icon name="phone" /></a>@endif
    @if ($org->whatsappUrl())<a class="chrome-float-btn chrome-float-wa" href="{{ $org->whatsappUrl() }}" target="_blank" rel="noopener" aria-label="WhatsApp us"><x-icon name="whatsapp" /></a>@endif
    <a class="chrome-float-pill" href="/page/contact-us" aria-label="Get support"><x-icon name="bolt" /><span class="chrome-float-label"> Get Support</span></a>
    @if (Route::has('donate'))<a class="chrome-float-pill chrome-float-donate" href="{{ route('donate') }}" aria-label="Donate"><x-icon name="heart" /><span class="chrome-float-label"> Donate</span></a>@endif
</div>
