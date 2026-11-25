@extends('layouts.admin')

@section('title', ($item->exists ? 'Edit ' : 'Add ') . $def['singular'])

@section('content')
    @php
        $isEdit = $item->exists;
        $action = $isEdit ? route('modules.update', [$key, $item->id]) : route('modules.store', $key);
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $isEdit ? 'Edit' : 'Add' }} {{ $def['singular'] }}</h1>
            <p class="page-subtitle">{{ $def['title'] }}</p>
        </div>
        <a href="{{ route('modules.index', $key) }}" class="btn btn-light">&larr; Back to list</a>
    </div>

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card form-card" novalidate>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="form-grid">
            @foreach ($def['fields'] as $field)
                @include('admin.modules._field', ['field' => $field, 'item' => $item])
            @endforeach
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">{{ $isEdit ? 'Save changes' : 'Save ' . $def['singular'] }}</button>
            <a href="{{ route('modules.index', $key) }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
@endsection
