@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <h2 class="auth-title">Sign in</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input class="form-input" type="email" id="email" name="email"
                   value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input class="form-input" type="password" id="password" name="password"
                   required autocomplete="current-password">
        </div>

        <label class="form-check">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
        </label>

        <button type="submit" class="btn btn-block">Log in</button>
    </form>
@endsection