@extends('layouts.site')
@section('title', 'Thank you')
@section('content')
    <section class="page-intro"><h1>Thank you for your support</h1>
        <p>We have received your donation details. Our team confirms the payment and sends your receipt.</p></section>
    <p><a href="{{ route('home') }}">Back to home</a></p>
@endsection
