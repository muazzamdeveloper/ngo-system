@extends('layouts.admin')
@section('title', 'Access for ' . $staff->name)
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Access for {{ $staff->name }}</h1>
            <p class="page-subtitle">{{ $staff->email }} &middot; Starting role: {{ $staff->role?->label ?? 'none' }}. Only the ticked modules are open to this account.</p>
        </div>
        <a href="{{ route('access.index') }}" class="btn btn-light">Back to user access</a>
    </div>

    <form method="POST" action="{{ route('access.preset', $staff) }}" class="card form-card">
        @csrf
        <h2 class="section-title">Copy a preset</h2>
        <p class="form-hint">Replaces this account's ticks with a role's permissions. Other accounts are not affected.</p>
        <div class="asset-form">
            <select name="role_id" class="form-input" aria-label="Preset role">
                @foreach ($presets as $preset)
                    <option value="{{ $preset->id }}" @selected($staff->role_id === $preset->id)>{{ $preset->label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-light">Copy preset</button>
        </div>
    </form>

    <form method="POST" action="{{ route('access.update', $staff) }}" class="card form-card">
        @csrf @method('PUT')
        @foreach ($groups as $group => $rows)
            <h2 class="section-title toggle-heading">{{ $group }}</h2>
            <div class="toggle-grid">
                @foreach ($rows as $row)
                    <label class="toggle-card">
                        <span>{{ $row['label'] }}</span>
                        <input type="checkbox" class="switch" name="permissions[]" value="{{ $row['id'] }}" @checked(in_array($row['id'], $granted, true))>
                    </label>
                @endforeach
            </div>
        @endforeach
        <div class="form-actions">
            <button type="submit" class="btn">Save access</button>
            <a href="{{ route('access.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
@endsection
