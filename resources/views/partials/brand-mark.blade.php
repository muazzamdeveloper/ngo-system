{{-- The logo (or the round initials badge when there is no logo) plus the name. Expects $org. --}}
@if ($org->logoUrl())
    <img class="brand-logo" src="{{ $org->logoUrl() }}" alt="{{ $org->name }}">
@else
    <span class="brand-badge {{ $badgeClass ?? '' }}">{{ $org->initials() }}</span>
@endif
<span class="brand-name">{{ $org->name }}</span>
