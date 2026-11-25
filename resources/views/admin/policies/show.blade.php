@extends('layouts.admin')
@section('title', $policy->title)
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $policy->title }}</h1>
            <p class="page-subtitle"><a href="{{ route('policies.index') }}">Policies</a> / {{ $policy->title }}</p>
        </div>
        <div class="actions-row">
            <form method="POST" action="{{ route('policies.publish', $type) }}">@csrf
                <button class="btn btn-light">{{ $policy->is_published ? 'Hide from site' : 'Publish' }}</button>
            </form>
            @if ($policy->is_published)
                <a class="btn btn-light" href="{{ route('policy.show', $type) }}" target="_blank" rel="noopener">View public page</a>
            @endif
            <a href="{{ route('policies.sections.create', $type) }}" class="btn">+ Add section</a>
        </div>
    </div>
    <div class="card table-card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Order</th><th>Icon</th><th>Section title</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
                <tbody>
                    @forelse ($sections as $section)
                        <tr>
                            <td>{{ $section->sort_order }}</td>
                            <td><code>{{ $section->icon }}</code></td>
                            <td class="strong">{{ $section->title }}</td>
                            <td><span class="status-badge status-{{ $section->status }}">{{ ucfirst($section->status) }}</span></td>
                            <td class="col-actions">
                                <div class="row-actions">
                                    <a class="btn btn-small btn-light" href="{{ route('policies.sections.edit', [$type, $section]) }}">Edit</a>
                                    <form method="POST" action="{{ route('policies.sections.destroy', [$type, $section]) }}" onsubmit="return confirm('Delete this section?');">@csrf @method('DELETE')<button class="btn btn-small btn-danger">Delete</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-cell">No sections yet. <a href="{{ route('policies.sections.create', $type) }}">Add the first section</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
