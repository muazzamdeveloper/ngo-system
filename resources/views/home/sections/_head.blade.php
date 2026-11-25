@if (filled($section->title) || filled($section->subtitle))
    <div class="hs-head">
        @if (filled($section->title)) <h2>{{ $section->title }}</h2> @endif
        @if (filled($section->subtitle)) <p>{{ $section->subtitle }}</p> @endif
    </div>
@endif
