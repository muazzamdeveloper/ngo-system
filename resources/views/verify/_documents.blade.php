{{-- The two download buttons. $links is null until the Aadhaar number has been verified. --}}
@php($ready = is_array($links ?? null))

<section class="site-card">
    <div class="section-head">
        <span class="section-icon"><x-icon name="download" /></span>
        <div>
            <h2>Your documents</h2>
            <p>{{ $ready ? 'Your links work for ' . $minutes . ' minutes.' : 'Verify your Aadhaar number above to unlock the downloads.' }}</p>
        </div>
    </div>

    <div class="doc-tiles">
        @foreach ([
            ['id-card', 'Download member card', 'Your ID card, front and back (PDF)', 'id'],
            ['joining-letter', 'Download member joining letter', 'Your official joining letter (PDF)', 'file'],
        ] as [$key, $title, $subtitle, $icon])
            @if ($ready)
                <a class="doc-tile" href="{{ $links[$key] }}" rel="nofollow">
                    <x-icon :name="$icon" />
                    <span><strong>{{ $title }}</strong><small>{{ $subtitle }}</small></span>
                </a>
            @else
                <div class="doc-tile is-disabled" aria-disabled="true">
                    <x-icon name="lock" />
                    <span><strong>{{ $title }}</strong><small>Verify your Aadhaar number first</small></span>
                </div>
            @endif
        @endforeach
    </div>
</section>
