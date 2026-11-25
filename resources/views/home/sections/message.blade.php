<section class="hs hs-message">
    <div class="hs-inner">
        <div class="message-grid">
            @if ($section->imageUrl())
                <div class="message-photo"><img src="{{ $section->imageUrl() }}" alt="{{ $section->title }}"></div>
            @endif

            <div class="message-text">
                @if (filled($section->title)) <h2>{{ $section->title }}</h2> @endif
                <div class="prose">{!! $section->bodyHtml() !!}</div>

                @if ($section->image2Url())
                    <img class="message-signature" src="{{ $section->image2Url() }}" alt="Signature">
                @endif
                @if (filled($section->subtitle)) <p class="message-name">{{ $section->subtitle }}</p> @endif
            </div>
        </div>
    </div>
</section>
