@extends('layouts.admin')

@section('title', 'Roles and permissions')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Roles &amp; permissions</h1>
        <a href="{{ route('roles.create') }}" class="btn">+ New role</a>
    </div>

    @if ($errors->has('role'))
        <div class="alert alert-danger">{{ $errors->first('role') }}</div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Users</th>
                        <th>Permissions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td><strong>{{ $role->label }}</strong></td>
                            <td class="text-muted">{{ $role->description ?? '-' }}</td>
                            <td>{{ $role->is_system ? 'System' : 'Custom' }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>
                                @if ($role->name === 'super-admin')
                                    All
                                @else
                                    {{ $role->permissions_count }}
                                @endif
                            </td>
                            <td>
                                <div class="actions-row">
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-small btn-light">
                                        {{ $role->isLocked() ? 'View' : 'Edit' }}
                                    </a>

                                    @unless ($role->is_system)
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-small btn-danger"
                                                    onclick="return confirm('Delete this role?');">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted" style="margin-top: 1rem;">
        The Super Admin always has every permission. Managing roles and reading the audit log are
        reserved for the Super Admin and cannot be given to any role.
    </p>
@endsection
