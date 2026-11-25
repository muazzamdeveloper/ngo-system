@extends('layouts.site')
@php($def = \App\Support\PageContent::definition($slug))
@section('title', $def['title'])
@section('content')
    <div class="page-intro">
        <span class="page-eyebrow">{{ $def['eyebrow'] ?? '' }}</span>
        <h1>{{ $def['title'] }}</h1>
    </div>

    <div class="public-article content-page cms-page">
        @foreach (\App\Support\PageContent::sections($slug) as $section)
            @php($blocks = $section['blocks'])
            <section class="cms-section cms-layout-{{ $section['layout'] }}">
                @if ($section['label'] !== '')
                    <h2 class="cms-section-title">{{ $section['label'] }}</h2>
                @endif

                @if ($section['layout'] === 'cards')
                    {{-- Blocks named card1_title, card1_text, card1_image ... form one card each. --}}
                    @php($cards = [])
                    @foreach ($blocks as $key => $block)
                        @php(preg_match('/^(card\d+)_/', $key, $m))
                        @php($cards[$m[1] ?? 'other'][$key] = $block)
                    @endforeach
                    <div class="cms-cards">
                        @foreach ($cards as $cardKey => $cardBlocks)
                            <article class="cms-card">
                                @foreach ($cardBlocks as $key => $block)
                                    @if ($block['type'] === 'image' && \App\Support\PageContent::image($slug, $key))
                                        <img class="cms-card-image" src="{{ \App\Support\PageContent::image($slug, $key) }}" alt="{{ $block['label'] }}" loading="lazy">
                                    @elseif ($block['type'] === 'text')
                                        <h3 class="cms-card-title">{{ \App\Support\PageContent::text($slug, $key) }}</h3>
                                    @elseif ($block['type'] === 'richtext')
                                        <div class="prose cms-card-text">{!! \App\Support\PageContent::html($slug, $key) !!}</div>
                                    @endif
                                @endforeach
                            </article>
                        @endforeach
                    </div>
                @elseif ($section['layout'] === 'split')
                    {{-- One image beside the text. The side comes from the section's 'image' setting (left or right). --}}
                    <div class="cms-split cms-image-{{ $section['image'] }}">
                        <div class="cms-split-media">
                            @foreach ($blocks as $key => $block)
                                @if ($block['type'] === 'image' && \App\Support\PageContent::image($slug, $key))
                                    <figure class="content-figure"><img src="{{ \App\Support\PageContent::image($slug, $key) }}" alt="{{ $block['label'] }}" loading="lazy"></figure>
                                @endif
                            @endforeach
                        </div>
                        <div class="cms-split-text">
                            @foreach ($blocks as $key => $block)
                                @if ($block['type'] === 'text' && preg_match('/(^|_)(title|heading)$/', $key))
                                    <h3 class="content-heading">{{ \App\Support\PageContent::text($slug, $key) }}</h3>
                                @elseif ($block['type'] === 'text')
                                    <p class="cms-line">{{ \App\Support\PageContent::text($slug, $key) }}</p>
                                @elseif ($block['type'] === 'richtext')
                                    <div class="prose content-text">{!! \App\Support\PageContent::html($slug, $key) !!}</div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @else
                    @foreach ($blocks as $key => $block)
                        @if ($block['type'] === 'text')
                            <h3 class="content-heading">{{ \App\Support\PageContent::text($slug, $key) }}</h3>
                        @elseif ($block['type'] === 'richtext')
                            <div class="prose content-text">{!! \App\Support\PageContent::html($slug, $key) !!}</div>
                        @elseif ($block['type'] === 'image' && \App\Support\PageContent::image($slug, $key))
                            <figure class="content-figure"><img src="{{ \App\Support\PageContent::image($slug, $key) }}" alt="{{ $block['label'] }}" loading="lazy"></figure>
                        @endif
                    @endforeach
                @endif
            </section>
        @endforeach
    </div>

    @if ($slug === 'contact-us')
        @include('public.partials.contact-form')
    @endif
@endsection
