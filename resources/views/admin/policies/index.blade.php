@extends('layouts.admin')
@section('title', 'Policies & compliance')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Policy management</h1>
            <p class="page-subtitle">Edit every legal policy as ordered sections. Published policies appear on the public site.</p>
        </div>
    </div>
    <div class="policy-grid">
        @foreach ($policies as $policy)
            <a href="{{ route('policies.show', $policy->type) }}" class="card policy-card">
                <span class="policy-type">Policy type</span>
                <span class="policy-title">{{ $policy->title }}</span>
                <span class="muted small">{{ $policy->sections_count }} section(s) &middot; {{ $policy->is_published ? 'Published' : 'Hidden' }}</span>
            </a>
        @endforeach
    </div>
@endsection
