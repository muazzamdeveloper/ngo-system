@if ($section->items->isNotEmpty())
    <section class="hero-slider" data-hero data-interval="6000" role="region" aria-roledescription="carousel" aria-label="Highlights">
        <div class="hero-slides">
            @foreach ($section->items as $i => $slide)
                <div class="hero-slide @if ($i === 0) is-active @endif" role="group" aria-label="Slide {{ $i + 1 }} of {{ $section->items->count() }}"
                     @if ($slide->imageUrl()) style="background-image: url('{{ $slide->imageUrl() }}')" @endif>
                    <div class="hero-overlay"></div>
                    <div class="hero-content">
                        @if (filled($slide->title)) <h2>{{ $slide->title }}</h2> @endif
                        @if (filled($slide->body)) <p>{!! nl2br(e($slide->body)) !!}</p> @endif
                        @if (filled($slide->link_label) && filled($slide->link_url))
                            <a class="btn hero-button" href="{{ $slide->link_url }}" @if (str_starts_with($slide->link_url, 'http')) target="_blank" rel="noopener" @endif>{{ $slide->link_label }}</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($section->items->count() > 1)
            <button type="button" class="hero-arrow hero-prev" aria-label="Previous slide">&lsaquo;</button>
            <button type="button" class="hero-arrow hero-next" aria-label="Next slide">&rsaquo;</button>
            <div class="hero-dots">
                @foreach ($section->items as $i => $slide)
                    <button type="button" class="@if ($i === 0) is-active @endif" aria-label="Go to slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </section>
@endif
