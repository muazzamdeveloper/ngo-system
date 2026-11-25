@extends('layouts.admin')
@section('title', 'My profile')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">My profile</h1>
            <p class="page-subtitle">Your name and login email. Your role is set by the super admin.</p>
        </div>
    </div>
    <div class="split-grid">
        <form method="POST" action="{{ route('profile.update') }}" class="card form-card">
            @csrf @method('PUT')
            <div class="form-group"><label class="form-label" for="name">Name <span class="req">*</span></label>
                <input class="form-input" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="120" required>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror</div>
            <div class="form-group"><label class="form-label" for="email">Email <span class="req">*</span></label>
                <input class="form-input" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="190" required>
                @error('email') <div class="form-error">{{ $message }}</div> @enderror</div>
            <div class="form-actions"><button type="submit" class="btn">Save profile</button></div>
        </form>
        <div class="card form-card">
            <h2 class="section-title">Password</h2>
            <p class="small muted">Change your password on the password page.</p>
            <a href="{{ route('password.edit') }}" class="btn btn-light">Change password</a>
        </div>
    </div>
@endsection
