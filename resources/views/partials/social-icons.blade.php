{{-- Round social buttons for every link that is set. Expects $org. --}}
@if ($org->facebook_url || $org->instagram_url || $org->youtube_url || $org->whatsappUrl())
    <span class="social-icons">
        @if ($org->facebook_url)
            <a href="{{ $org->facebook_url }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><x-icon name="facebook" /></a>
        @endif
        @if ($org->instagram_url)
            <a href="{{ $org->instagram_url }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><x-icon name="instagram" /></a>
        @endif
        @if ($org->youtube_url)
            <a href="{{ $org->youtube_url }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><x-icon name="youtube" /></a>
        @endif
        @if ($org->whatsappUrl())
            <a href="{{ $org->whatsappUrl() }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><x-icon name="whatsapp" /></a>
        @endif
    </span>
@endif
