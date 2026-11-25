@extends('layouts.admin')

@section('title', 'Edit')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Edit {{ strtolower($section->definition()['item_name'] ?? 'item') }}</h1>
        <a href="{{ route('home-sections.edit', $section) }}" class="btn btn-light">Back to the section</a>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('home-items.update', $item) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('admin.home._item_fields', ['section' => $section, 'item' => $item])
            <div class="form-actions"><button type="submit" class="btn">Save</button></div>
        </form>
    </div>
@endsection
