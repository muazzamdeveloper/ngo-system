@extends('layouts.site')
@section('title', $def['label'])
@section('content')
    @php
        $isVideo = $key === 'videos'; $isGallery = $key === 'gallery';
        $eyebrows = [
            'campaigns' => 'Support our cause', 'events' => 'Participate & engage', 'press-releases' => 'Press room',
            'projects' => 'Our work', 'awards' => 'Recognition', 'gallery' => 'Visual journey', 'videos' => 'Watch & learn',
        ];
        $eyebrow = $eyebrows[$key] ?? 'Latest updates';
    @endphp
    <section class="page-intro list-intro">
        <p class="page-eyebrow">{{ $eyebrow }}</p>
        <h1>{{ $def['label'] }}</h1>
        @if (! empty($def['subtitle'])) <p>{{ $def['subtitle'] }}</p> @endif
    </section>

    @if ($items->isEmpty())
        <p class="empty-public">Nothing has been published here yet. Please check back soon.</p>
    @elseif ($isVideo)
        <div class="video-grid">
            @foreach ($items as $video)
                @php $embed = \App\Support\YouTube::embedUrl($video->youtube_url); @endphp
                <div class="video-card">
                    @if ($embed)
                        <iframe src="{{ $embed }}" title="{{ $video->title }}" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    @endif
                    <h2>{{ $video->title }}</h2>
                </div>
            @endforeach
        </div>
    @elseif ($isGallery)
        <div class="photo-grid">
            @foreach ($items as $photo)
                @php $url = \App\Services\SiteMedia::url($photo->image_path); @endphp
                @if ($url)
                    <figure class="photo-card">
                        <img src="{{ $url }}" alt="{{ $photo->title ?: $def['label'] }}" loading="lazy">
                        @if ($photo->title)<figcaption>{{ $photo->title }}</figcaption>@endif
                    </figure>
                @endif
            @endforeach
        </div>
    @elseif ($key === 'campaigns')
        <div class="card-grid">
            @foreach ($items as $campaign)
                @php $url = \App\Services\SiteMedia::url($campaign->image_path); @endphp
                <article class="public-card campaign-card">
                    @if ($url)<img src="{{ $url }}" alt="" loading="lazy">@endif
                    <div class="public-card-body">
                        <h2>{{ $campaign->title }}</h2>
                        @if ($campaign->short_description)<p>{{ $campaign->short_description }}</p>@endif
                        <div class="progress" aria-hidden="true"><span style="width: {{ $campaign->progress }}%"></span></div>
                        <div class="campaign-figures">
                            <span>Raised <strong>₹{{ number_format($campaign->raised, 0) }}</strong></span>
                            <span>Goal <strong>₹{{ number_format((float) $campaign->goal_amount, 0) }}</strong></span>
                        </div>
                        <div class="campaign-actions">
                            <a href="{{ route('public.campaigns.show', $campaign->slug) }}" class="btn btn-light btn-small">Read more</a>
                            <a href="{{ route('donate', ['campaign' => $campaign->id]) }}" class="btn btn-small">Donate now</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="card-grid">
            @foreach ($items as $item)
                @php
                    $url = \App\Services\SiteMedia::url($item->image_path ?? $item->logo_path ?? null);
                    $detail = ($def['public']['detail'] ?? false) && isset($item->slug);
                @endphp
                <article class="public-card">
                    @if ($url)<img src="{{ $url }}" alt="" loading="lazy">@endif
                    <div class="public-card-body">
                        @if (isset($item->event_date))
                            <p class="public-date">{{ $item->event_date->format('d M Y') }}@if($item->location) &middot; {{ $item->location }}@endif</p>
                        @elseif (isset($item->release_date) && $item->release_date)
                            <p class="public-date">{{ $item->release_date->format('d M Y') }}</p>
                        @elseif (isset($item->award_date) && $item->award_date)
                            <p class="public-date">{{ $item->award_date->format('d M Y') }}</p>
                        @endif
                        <h2>{{ $item->title }}</h2>
                        @php $summary = $item->short_description ?? $item->excerpt ?? null; @endphp
                        @if ($summary)<p>{{ $summary }}</p>@endif
                        @if ($detail)<a href="{{ route('public.' . $key . '.show', $item->slug) }}" class="read-more-btn">Read More &rarr;</a>@endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <div class="pagination-public">{{ $items->links('vendor.pagination.custom') }}</div>
@endsection
