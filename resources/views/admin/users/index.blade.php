@extends('layouts.admin')

@section('title', 'Staff users')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Staff users</h1>
        <a href="{{ route('users.create') }}" class="btn">+ Add staff user</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $staff)
                        @php($isYou = $staff->id === auth()->id())
                        <tr>
                            <td>
                                <strong>{{ $staff->name }}</strong>
                                @if ($isYou) <span class="text-muted">(you)</span> @endif
                            </td>
                            <td>{{ $staff->email }}</td>
                            <td>
                                @if ($staff->isSuperAdmin())
                                    Super Admin
                                @else
                                    {{ $staff->role?->label ?? 'No role' }}
                                @endif
                            </td>
                            <td>
                                @if ($staff->is_active)
                                    <span class="badge badge-approved">Active</span>
                                @else
                                    <span class="badge badge-deactivated">Disabled</span>
                                @endif
                            </td>
                            <td>{{ $staff->created_at->format('d M Y') }}</td>
                            <td>
                                @if (! $isYou && ! $staff->isSuperAdmin() && (! $staff->role || auth()->user()->canAssignRole($staff->role)))
                                    <div class="actions-row">
                                        <a href="{{ route('users.edit', $staff) }}" class="btn btn-small btn-light">Edit</a>

                                        @if ($staff->is_active)
                                            <form method="POST" action="{{ route('users.deactivate', $staff) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-small btn-danger"
                                                        onclick="return confirm('Disable this account?');">Disable</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('users.activate', $staff) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-small">Enable</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="text-muted">Members' logins are managed from each member's profile.</span>
            {{ $users->links() }}
        </div>
    </div>
@endsection
