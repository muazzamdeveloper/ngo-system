@extends('layouts.site')

@section('title', 'Document not ready')

@section('hero')
    <span class="hero-pill">Member verification</span>
    <h1>Document not ready</h1>
    <p class="hero-text">This document cannot be downloaded right now.</p>
@endsection

@section('content')
    <section class="site-card thanks-box">
        <p>Please try again later or contact the organization.</p>
        <p><a class="btn" href="{{ route('verify.create') }}">Back</a></p>
    </section>
@endsection
