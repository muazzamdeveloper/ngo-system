@extends('layouts.admin')
@section('title', 'Security permissions')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Coordinator security permissions</h1>
            <p class="page-subtitle">Sets the starting preset for new Coordinator accounts. To change an existing account, use User access.</p>
        </div>
        <a href="{{ route('access.index') }}" class="btn btn-light">User access</a>
    </div>

    <form method="POST" action="{{ route('security.update') }}" class="card form-card">
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
            <button type="submit" class="btn">Save coordinator permissions</button>
            <a href="{{ route('dashboard') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
@endsection
