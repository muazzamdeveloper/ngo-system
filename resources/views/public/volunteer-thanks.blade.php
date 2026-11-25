@extends('layouts.site')
@section('title', 'Thank you')
@section('content')
    <section class="page-intro"><h1>Thank you for volunteering</h1><p>We have received your details and will be in touch.</p></section>
    <p><a href="{{ route('home') }}">Back to home</a></p>
@endsection
