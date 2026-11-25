<section class="hs hs-banner-wrap">
    <div class="hs-inner">
        <div class="hs-banner">
            @if (filled($section->title)) <h2>{{ $section->title }}</h2> @endif
            @if (filled($section->subtitle)) <p>{{ $section->subtitle }}</p> @endif

            @if ($section->items->isNotEmpty())
                <div class="banner-facts">
                    @foreach ($section->items as $item)
                        <div class="banner-fact">
                            <strong>{{ $item->title }}</strong>
                            @if (filled($item->subtitle)) <span>{{ $item->subtitle }}</span> @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
