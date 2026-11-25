@extends('layouts.admin')

@section('title', $role->label)

@section('content')
    <h1 class="page-title">{{ $role->label }}</h1>

    <div class="card">
        @if ($role->name === 'super-admin')
            <p>The Super Admin role always has every permission and cannot be changed.</p>
            <a href="{{ route('roles.index') }}" class="btn btn-light">Back</a>
        @else
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf
                @method('PUT')

                @include('admin.roles._form')

                <div class="form-actions">
                    <button type="submit" class="btn">Save changes</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        @endif
    </div>
@endsection
