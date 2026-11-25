@extends('layouts.admin')

@section('title', 'New role')

@section('content')
    <h1 class="page-title">New role</h1>

    <div class="card">
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf

            @include('admin.roles._form')

            <div class="form-actions">
                <button type="submit" class="btn">Create role</button>
                <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection
