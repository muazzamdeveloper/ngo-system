<section class="hs hs-tint">
    <div class="hs-inner">
        @include('home.sections._head')

        @if ($section->items->isNotEmpty())
            <div class="tabs" data-tabs>
                <div class="tab-list" role="tablist">
                    @foreach ($section->items as $i => $item)
                        <button type="button" role="tab" class="@if ($i === 0) is-active @endif" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">{{ $item->title }}</button>
                    @endforeach
                </div>
                @foreach ($section->items as $i => $item)
                    <div class="tab-panel @if ($i === 0) is-active @endif" role="tabpanel">
                        <p>{!! nl2br(e($item->body)) !!}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if (filled($section->link_label) && filled($section->link_url))
            <p class="hs-more"><a class="btn" href="{{ $section->link_url }}">{{ $section->link_label }}</a></p>
        @endif
    </div>
</section>
