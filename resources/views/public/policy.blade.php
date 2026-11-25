@extends('layouts.site')
@section('title', $policy->title)
@section('content')
    <section class="page-intro"><h1>{{ $policy->title }}</h1></section>
    <div class="policy-public">
        @forelse ($sections as $section)
            <section class="policy-section">
                @php($policyIcons = ["fa-lock" => "lock", "fa-envelope" => "mail", "fa-clock" => "clock", "fa-shield" => "shield", "fa-user-shield" => "shield", "fa-file-invoice" => "file", "fa-receipt" => "file", "fa-download" => "download", "fa-link" => "arrow", "fa-circle-info" => "check"])
                <h2><x-icon name="{{ $policyIcons[$section->icon] ?? "check" }}" class="policy-icon" /> {{ $section->title }}</h2>
                <div class="prose">{!! \App\Support\HtmlCleaner::clean($section->content) !!}</div>
            </section>
        @empty
            <p>This policy is being updated. Please check back soon.</p>
        @endforelse
    </div>
@endsection
