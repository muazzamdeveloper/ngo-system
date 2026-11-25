<section class="hs">
    <div class="hs-inner">
        @include('home.sections._head')

        <div class="card-grid">
            @foreach ($section->items as $item)
                <article class="info-card">
                    @if ($item->imageUrl()) <img src="{{ $item->imageUrl() }}" alt=""> @endif
                    @if (filled($item->title)) <h3>{{ $item->title }}</h3> @endif
                    @if (filled($item->body)) <p>{!! nl2br(e($item->body)) !!}</p> @endif
                    @if (filled($item->link_label) && filled($item->link_url))
                        <a class="info-link" href="{{ $item->link_url }}" @if (str_starts_with($item->link_url, 'http')) target="_blank" rel="noopener" @endif>{{ $item->link_label }} &rarr;</a>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
