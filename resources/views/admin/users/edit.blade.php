@extends('layouts.admin')

@section('title', 'Edit staff user')

@section('content')
    <h1 class="page-title">Edit {{ $staff->name }}</h1>

    <div class="card">
        <form method="POST" action="{{ route('users.update', $staff) }}">
            @csrf
            @method('PUT')

            @include('admin.users._form', ['editing' => true])

            <div class="form-actions">
                <button type="submit" class="btn">Save changes</button>
                <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection
