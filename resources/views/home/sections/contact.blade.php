@php($org = \App\Models\OrganizationSetting::current())
<section class="hs">
    <div class="hs-inner">
        @include('home.sections._head')

        <div class="contact-grid">
            <div class="contact-info">
                @if (filled($section->body)) <div class="prose">{!! $section->bodyHtml() !!}</div> @endif

                @if ($org->address || $org->address_secondary)
                    <div class="contact-row"><x-icon name="pin" />
                        <div>
                            @if ($org->address) <p><strong>Head office:</strong> {{ $org->address }}</p> @endif
                            @if ($org->address_secondary) <p><strong>Registered office:</strong> {{ $org->address_secondary }}</p> @endif
                        </div>
                    </div>
                @endif
                @if ($org->phone)
                    <div class="contact-row"><x-icon name="phone" /><div><p><a href="{{ $org->phoneHref() }}">{{ $org->phone }}</a></p></div></div>
                @endif
                @if ($org->email)
                    <div class="contact-row"><x-icon name="mail" /><div><p><a href="mailto:{{ $org->email }}">{{ $org->email }}</a></p></div></div>
                @endif
                @if ($org->working_hours)
                    <div class="contact-row"><x-icon name="clock" /><div><p>{{ $org->working_hours }}</p></div></div>
                @endif
            </div>

            @if (filled($section->link_url))
                <div class="contact-map">
                    <iframe src="{{ $section->link_url }}" title="Map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            @endif
        </div>
    </div>
</section>
