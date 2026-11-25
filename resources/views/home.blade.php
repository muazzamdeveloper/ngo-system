@extends('layouts.site')
@php
    use App\Services\SiteMedia;
    use App\Support\PageContent;
    use App\Support\YouTube;
    $t = fn (string $key) => PageContent::text('home', $key);
    $img = fn (string $key) => PageContent::image('home', $key);
    $route = fn (string $name, $fallback = '#') => \Illuminate\Support\Facades\Route::has($name) ? route($name) : $fallback;
    $money = fn ($n) => '₹' . number_format((float) $n, 0);
@endphp
@section('title', 'Home')
@section('main_class', 'home-main')

@section('content')
<div class="hm">

    @if ($sliders->isNotEmpty())
        <section class="hm-hero" aria-label="Highlights">
            @foreach ($sliders as $slide)
                <figure class="hm-hero-slide @if (! $loop->first) is-hidden @endif">
                    <img src="{{ SiteMedia::url($slide->image_path) }}" alt="{{ $slide->title }}">
                    @if (filled($slide->title))<figcaption>{{ $slide->title }}</figcaption>@endif
                </figure>
            @endforeach
        </section>
    @elseif ($img('hero_image'))
        <section class="hm-hero" aria-label="Highlights">
            <figure class="hm-hero-slide"><img src="{{ $img('hero_image') }}" alt=""></figure>
        </section>
    @endif

    <nav class="hm-quick" aria-label="Quick links">
        <a href="{{ $route('apply.create', '/apply') }}"><x-icon name="user-plus" /> Become a Member</a>
        <a href="{{ $route('donate', '/donate') }}"><x-icon name="heart" /> Donate</a>
        <a href="{{ $route('public.events.index', '/events') }}"><x-icon name="calendar" /> Upcoming Events</a>
        <a href="/page/management-team"><x-icon name="team" /> Management Team</a>
        <a href="{{ $route('public.campaigns.index', '/campaigns') }}"><x-icon name="megaphone" /> Crowdfunding</a>
    </nav>

    <div class="hm-columns">
        <section class="hm-panel">
            <h2 class="hm-heading">{{ $t('h_events') }}</h2>
            @forelse ($events as $event)
                <a class="hm-event" href="{{ \Illuminate\Support\Facades\Route::has('public.events.show') ? route('public.events.show', $event->slug) : '#' }}">
                    <span class="hm-event-date">{{ $event->event_date->format('d M') }}</span>
                    <span><strong>{{ $event->title }}</strong>@if ($event->location)<small>{{ $event->location }}</small>@endif</span>
                </a>
            @empty
                <p class="hm-empty">No upcoming events. Add events under Events in the admin.</p>
            @endforelse
        </section>

        <section class="hm-panel">
            <h2 class="hm-heading">{{ $t('h_activities') }}</h2>
            @forelse ($projects as $project)
                <article class="hm-activity">
                    @if ($project->image_path)<img src="{{ SiteMedia::url($project->image_path) }}" alt="">@endif
                    <h3>{{ $project->title }}</h3>
                    @if ($project->description)<p>{{ \Illuminate\Support\Str::limit(strip_tags($project->description), 160) }}</p>@endif
                </article>
            @empty
                <p class="hm-empty">No activities yet. Add projects under Projects in the admin.</p>
            @endforelse
        </section>

        <section class="hm-panel">
            <h2 class="hm-heading">{{ $t('h_management') }}</h2>
            @forelse ($management as $person)
                <div class="hm-person">
                    @if ($person->photo_path)<img src="{{ SiteMedia::url($person->photo_path) }}" alt="{{ $person->name }}">@endif
                    <strong>{{ $person->name }}</strong>
                    @if ($person->designation)<span>{{ $person->designation }}</span>@endif
                </div>
            @empty
                <div class="hm-person">
                    @if ($img('management_image'))<img src="{{ $img('management_image') }}" alt="">@endif
                    <strong>{{ $t('management_name') }}</strong>
                    <span>{{ $t('management_role') }}</span>
                </div>
            @endforelse
            <a class="hm-button" href="/page/management-team">View all</a>
        </section>
    </div>

    <section class="hm-about">
        <h2 class="hm-heading">{{ $t('h_about') }}</h2>
        <div class="hm-about-inner">
            @if ($img('about_image'))<img class="hm-about-img" src="{{ $img('about_image') }}" alt="">@endif
            <div class="prose">{!! PageContent::html('home', 'about_text') !!}</div>
        </div>
        <a class="hm-button" href="/page/about-organisation">Read more about us</a>
    </section>

    @if ($campaigns->isNotEmpty())
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_campaigns') }}</h2>
            <div class="hm-cards">
                @foreach ($campaigns as $campaign)
                    <article class="hm-card">
                        @if ($campaign->image_path)<img src="{{ SiteMedia::url($campaign->image_path) }}" alt="">@endif
                        <div class="hm-card-body">
                            <h3>{{ $campaign->title }}</h3>
                            @if ($campaign->short_description)<p>{{ $campaign->short_description }}</p>@endif
                            <div class="progress"><span style="width: {{ $campaign->progress }}%"></span></div>
                            <div class="muted small">Raised {{ $money($campaign->raised) }} of {{ $money($campaign->goal_amount) }} ({{ $campaign->progress }}%)</div>
                            <div class="actions-row">
                                <a class="hm-button" href="{{ route('public.campaigns.show', $campaign->slug) }}">Read more</a>
                                <a class="hm-button hm-button-accent" href="{{ $route('donate', '/donate') }}?campaign={{ $campaign->id }}">Donate now</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @else
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_campaigns') }}</h2>
            <div class="hm-cards">
                @for ($i = 1; $i <= 3; $i++)
                    <article class="hm-card">
                        @if ($img("campaign{$i}_image"))<img src="{{ $img("campaign{$i}_image") }}" alt="">@endif
                        <div class="hm-card-body">
                            <h3>{{ $t("campaign{$i}_title") }}</h3>
                            <p>{{ $t("campaign{$i}_text") }}</p>
                            <div class="actions-row">
                                <a class="hm-button" href="{{ $route('public.campaigns.index', '/campaigns') }}">Read more</a>
                                <a class="hm-button hm-button-accent" href="{{ $route('donate', '/donate') }}">Donate now</a>
                            </div>
                        </div>
                    </article>
                @endfor
            </div>
        </section>
    @endif

    <section class="hm-section">
        <h2 class="hm-heading">{{ $t('h_objectives') }}</h2>
        <div class="hm-cards hm-cards-3">
            @foreach ([1, 2, 3] as $n)
                <article class="hm-objective">
                    @if ($img("objective_{$n}_image"))<img src="{{ $img("objective_{$n}_image") }}" alt="">@endif
                    <h3>{{ $t("objective_{$n}_title") }}</h3>
                    <p>{{ $t("objective_{$n}_text") }}</p>
                </article>
            @endforeach
        </div>
    </section>

    @if ($awards->isNotEmpty())
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_awards') }}</h2>
            <div class="hm-cards hm-cards-3">
                @foreach ($awards as $award)
                    <article class="hm-card">
                        @if ($award->image_path)<img src="{{ SiteMedia::url($award->image_path) }}" alt="{{ $award->title }}">@endif
                        <div class="hm-card-body">
                            @if ($award->award_date)<span class="public-date">{{ $award->award_date->format('F j, Y') }}</span>@endif
                            <h3>{{ $award->title }}</h3>
                            @if ($award->issuer)<p class="muted small">{{ $award->issuer }}</p>@endif
                            @if ($award->description)<p>{{ \Illuminate\Support\Str::limit(strip_tags($award->description), 140) }}</p>@endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @else
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_awards') }}</h2>
            <div class="hm-cards hm-cards-3">
                @for ($i = 1; $i <= 3; $i++)
                    <article class="hm-card">
                        @if ($img("award{$i}_image"))<img src="{{ $img("award{$i}_image") }}" alt="">@endif
                        <div class="hm-card-body">
                            <span class="public-date">{{ $t("award{$i}_date") }}</span>
                            <h3>{{ $t("award{$i}_title") }}</h3>
                            <p>{{ $t("award{$i}_text") }}</p>
                        </div>
                    </article>
                @endfor
            </div>
        </section>
    @endif

    @if ($videos->isNotEmpty())
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_videos') }}</h2>
            <div class="hm-cards hm-cards-3">
                @foreach ($videos as $video)
                    @php($embed = YouTube::embedUrl($video->youtube_url))
                    @if ($embed)
                        <article class="hm-card video-card">
                            <iframe src="{{ $embed }}" title="{{ $video->title }}" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                            <div class="hm-card-body"><h3>{{ $video->title }}</h3></div>
                        </article>
                    @endif
                @endforeach
            </div>
            <a class="hm-button" href="{{ $route('public.videos.index', '/videos') }}">View all videos</a>
        </section>
    @endif

    @if ($photos->isNotEmpty())
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_gallery') }}</h2>
            <div class="hm-photos">
                @foreach ($photos as $photo)
                    <a href="{{ $route('public.gallery.index', '/gallery') }}" class="hm-photo"><img src="{{ SiteMedia::url($photo->image_path) }}" alt="{{ $photo->title }}" loading="lazy"></a>
                @endforeach
            </div>
            <a class="hm-button" href="{{ $route('public.gallery.index', '/gallery') }}">View full gallery</a>
        </section>
    @else
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_gallery') }}</h2>
            <div class="hm-cards hm-cards-3">
                @for ($i = 1; $i <= 9; $i++)
                    @if ($img("gallery{$i}_image"))
                        <figure class="hm-photo"><img src="{{ $img("gallery{$i}_image") }}" alt="" loading="lazy"></figure>
                    @endif
                @endfor
            </div>
        </section>
    @endif

    @if ($news->isNotEmpty())
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_news') }}</h2>
            <div class="hm-cards">
                @foreach ($news as $item)
                    <article class="hm-card">
                        @if ($item->image_path)<img src="{{ SiteMedia::url($item->image_path) }}" alt="">@endif
                        <div class="hm-card-body">
                            <h3>{{ $item->title }}</h3>
                            @if ($item->excerpt)<p>{{ \Illuminate\Support\Str::limit($item->excerpt, 150) }}</p>@endif
                            <a class="hm-button" href="{{ route('public.press-releases.show', $item->slug) }}">Read more</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @else
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_news') }}</h2>
            <div class="hm-cards hm-cards-3">
                @for ($i = 1; $i <= 6; $i++)
                    <article class="hm-card">
                        @if ($img("news{$i}_image"))<img src="{{ $img("news{$i}_image") }}" alt="">@endif
                        <div class="hm-card-body">
                            <h3>{{ $t("news{$i}_title") }}</h3>
                            <a class="hm-button" href="{{ $route('public.press-releases.index', '/press-releases') }}">Read More</a>
                        </div>
                    </article>
                @endfor
            </div>
        </section>
    @endif

    @if ($donors->isNotEmpty())
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_donors') }}</h2>
            <div class="hm-donors">
                @foreach ($donors as $donor)
                    <div class="hm-donor">
                        <strong>{{ $donor->donor_name }}</strong>
                        <span class="money">{{ $money($donor->amount) }}</span>
                        <span class="muted small">{{ $donor->donated_at?->format('d M Y') }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($testimonials->isNotEmpty())
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_testimonials') }}</h2>
            <div class="hm-cards hm-cards-3">
                @foreach ($testimonials as $item)
                    <blockquote class="hm-quote">
                        <span class="stars" aria-label="{{ $item->rating }} stars">{{ str_repeat('★', (int) $item->rating) }}</span>
                        <p>“{{ $item->message }}”</p>
                        <cite><strong>{{ $item->name }}</strong>@if ($item->role)<span>{{ $item->role }}</span>@endif</cite>
                    </blockquote>
                @endforeach
            </div>
        </section>
    @endif

    @if ($partners->isNotEmpty())
        <section class="hm-section">
            <h2 class="hm-heading">{{ $t('h_partners') }}</h2>
            <div class="hm-partners">
                @foreach ($partners as $partner)
                    @php($logo = SiteMedia::url($partner->logo_path))
                    @if ($partner->website_url)<a href="{{ $partner->website_url }}" target="_blank" rel="noopener" title="{{ $partner->name }}">@endif
                        @if ($logo)<img src="{{ $logo }}" alt="{{ $partner->name }}">@else<span>{{ $partner->name }}</span>@endif
                    @if ($partner->website_url)</a>@endif
                @endforeach
            </div>
        </section>
    @endif

</div>
@endsection
