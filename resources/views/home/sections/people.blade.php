<section class="hs hs-tint">
    <div class="hs-inner">
        @include('home.sections._head')

        <div class="people-grid">
            @foreach ($section->items as $item)
                <figure class="person">
                    <div class="person-photo">
                        @if ($item->imageUrl()) <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}"> @endif
                    </div>
                    <figcaption>
                        <strong>{{ $item->title }}</strong>
                        @if (filled($item->subtitle)) <span>{{ $item->subtitle }}</span> @endif
                    </figcaption>
                </figure>
            @endforeach
        </div>

        @if (filled($section->link_label) && filled($section->link_url))
            <p class="hs-more"><a class="btn" href="{{ $section->link_url }}">{{ $section->link_label }}</a></p>
        @endif
    </div>
</section>
