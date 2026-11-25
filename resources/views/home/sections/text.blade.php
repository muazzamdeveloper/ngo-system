<section class="hs">
    <div class="hs-inner">
        <div class="@if ($section->imageUrl()) text-split @endif">
            <div>
                @include('home.sections._head')
                <div class="prose">{!! $section->bodyHtml() !!}</div>
                @if (filled($section->link_label) && filled($section->link_url))
                    <p class="hs-more" style="text-align: left;"><a class="btn" href="{{ $section->link_url }}">{{ $section->link_label }}</a></p>
                @endif
            </div>
            @if ($section->imageUrl())
                <img class="text-image" src="{{ $section->imageUrl() }}" alt="">
            @endif
        </div>
    </div>
</section>
