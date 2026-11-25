<section class="hs hs-tint">
    <div class="hs-inner hs-narrow">
        @include('home.sections._head')

        @foreach ($section->items as $item)
            <details class="faq-item">
                <summary>{{ $item->title }}</summary>
                <p>{!! nl2br(e($item->body)) !!}</p>
            </details>
        @endforeach
    </div>
</section>
