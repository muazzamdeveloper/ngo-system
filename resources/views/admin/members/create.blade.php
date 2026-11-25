@extends('layouts.admin')

@section('title', 'Add member')

@section('content')
    <h1 class="page-title">Add member</h1>

    <div class="card">
        @if ($errors->any())
            <div class="alert alert-danger">Please fix the highlighted fields and try again.</div>
        @endif

        <form method="POST" action="{{ route('members.store') }}" enctype="multipart/form-data">
            @csrf

            @include('admin.members._fields')

            <div class="form-actions">
                <button type="submit" class="btn">Save member</button>
                <a href="{{ route('members.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection
