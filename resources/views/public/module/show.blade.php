@extends('layouts.site')
@section('title', $item->title)
@section('content')
    @php $url = \App\Services\SiteMedia::url($item->image_path ?? null); @endphp
    <article class="public-article">
        <p class="public-date">
            @if (isset($item->event_date)) {{ $item->event_date->format('d M Y') }}@if($item->event_time) at {{ \Illuminate\Support\Carbon::parse($item->event_time)->format('h:i A') }}@endif
                @if($item->location) &middot; {{ $item->location }}@endif
            @elseif (isset($item->release_date) && $item->release_date) {{ $item->release_date->format('d M Y') }}
            @endif
        </p>
        <h1>{{ $item->title }}</h1>
        @if ($url)<img class="public-hero" src="{{ $url }}" alt="">@endif
        <div class="prose">{!! \App\Support\HtmlCleaner::clean((string) ($item->description ?? '')) !!}</div>
        <p><a href="{{ route('public.' . $key . '.index') }}">&larr; Back to {{ strtolower($def['label']) }}</a></p>
    </article>
@endsection
