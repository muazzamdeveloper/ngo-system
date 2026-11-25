@extends('layouts.site')

@section('title', $page->title)

@section('hero')
    <span class="hero-pill">{{ $page->title }}</span>
    <h1>{{ $page->title }}</h1>
    @if (filled($page->summary))
        <p class="hero-text">{{ $page->summary }}</p>
    @endif
@endsection

@section('content')
    <article class="site-card page-body">
        @if ($page->imageUrl())
            <img class="page-image" src="{{ $page->imageUrl() }}" alt="">
        @endif
        <div class="page-text prose">{!! $page->html() !!}</div>
    </article>
@endsection
