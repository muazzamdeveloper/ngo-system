@extends('layouts.admin')

@section('title', 'Add staff user')

@section('content')
    <h1 class="page-title">Add staff user</h1>

    <div class="card">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            @include('admin.users._form', ['editing' => false])

            <div class="form-actions">
                <button type="submit" class="btn">Create user</button>
                <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection
