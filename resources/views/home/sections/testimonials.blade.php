<section class="hs">
    <div class="hs-inner">
        @include('home.sections._head')

        <div class="quote-grid">
            @foreach ($section->items as $item)
                <blockquote class="quote">
                    @if ($item->rating)
                        <div class="stars" aria-label="{{ $item->rating }} out of 5 stars">{{ str_repeat('★', $item->rating) }}<span>{{ str_repeat('★', 5 - $item->rating) }}</span></div>
                    @endif
                    @if (filled($item->body)) <p>{!! nl2br(e($item->body)) !!}</p> @endif
                    <footer>
                        @if ($item->imageUrl()) <img src="{{ $item->imageUrl() }}" alt=""> @endif
                        <span><strong>{{ $item->title }}</strong>@if (filled($item->subtitle)) <small>{{ $item->subtitle }}</small> @endif</span>
                    </footer>
                </blockquote>
            @endforeach
        </div>
    </div>
</section>
