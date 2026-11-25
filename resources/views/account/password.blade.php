@extends('layouts.member')

@section('title', 'Change password')

@section('content')
    <h1 class="page-title">Change password</h1>

    @if ($forced)
        <div class="alert alert-danger">Your account uses a temporary password. Please choose a new one to continue.</div>
    @endif

    <div class="card form-card">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="current_password">Current password</label>
                <input class="form-input" type="password" id="current_password" name="current_password"
                       required autocomplete="current-password">
                @error('current_password') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">New password</label>
                <input class="form-input" type="password" id="password" name="password"
                       required autocomplete="new-password">
                <div class="form-hint">At least 8 characters, with letters and numbers.</div>
                @error('password') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirm new password</label>
                <input class="form-input" type="password" id="password_confirmation" name="password_confirmation"
                       required autocomplete="new-password">
            </div>

            <button type="submit" class="btn">Change password</button>
        </form>
    </div>
@endsection
