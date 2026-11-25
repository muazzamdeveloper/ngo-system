@extends('layouts.admin')

@section('title', 'Edit member')

@section('content')
    <h1 class="page-title">Edit member</h1>

    <div class="card">
        @if ($errors->any())
            <div class="alert alert-danger">Please fix the highlighted fields and try again.</div>
        @endif

        <form method="POST" action="{{ route('members.update', $member) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.members._fields')

            <div class="form-actions">
                <button type="submit" class="btn">Save changes</button>
                <a href="{{ route('members.show', $member) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection
