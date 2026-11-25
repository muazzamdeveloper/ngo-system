<section class="hs">
    <div class="hs-inner">
        @include('home.sections._head')

        <div class="gallery-grid">
            @foreach ($section->items as $item)
                @if ($item->imageUrl())
                    <figure>
                        <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}" loading="lazy">
                        @if (filled($item->title)) <figcaption>{{ $item->title }}</figcaption> @endif
                    </figure>
                @endif
            @endforeach
        </div>

        @if (filled($section->link_label) && filled($section->link_url))
            <p class="hs-more"><a class="btn" href="{{ $section->link_url }}">{{ $section->link_label }}</a></p>
        @endif
    </div>
</section>
