@extends('layouts.admin')
@section('title', 'User access')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">User access</h1>
            <p class="page-subtitle">Choose the modules each staff account can open. Every account has its own list, so two managers can have different access.</p>
        </div>
        <a href="{{ route('security.edit') }}" class="btn btn-light">Coordinator presets</a>
    </div>

    <div class="card table-card">
        <form method="GET" action="{{ route('access.index') }}" class="toolbar">
            <input type="search" name="q" value="{{ $q }}" class="form-input toolbar-search" placeholder="Search name or email" aria-label="Search staff">
            <button type="submit" class="btn btn-light">Search</button>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Starting role</th>
                        <th>Modules granted</th>
                        <th>Status</th>
                        <th class="col-actions">Access</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="strong">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role?->label ?? 'No role' }}</td>
                            <td>{{ $user->direct_permissions_count }}</td>
                            <td><span class="status-badge status-{{ $user->is_active ? 'active' : 'inactive' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td><a class="btn btn-small btn-light" href="{{ route('access.edit', $user) }}">Manage access</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-cell">No staff accounts yet. Add one under <a href="{{ route('users.index') }}">Staff users</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $users->links() }}</div>
    </div>
@endsection
