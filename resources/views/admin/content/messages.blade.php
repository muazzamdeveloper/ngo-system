@extends('layouts.admin')
@section('title', 'Contact messages')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Contact messages</h1>
            <p class="page-subtitle">Messages sent from the Contact Us page. {{ $newCount }} new.</p>
        </div>
    </div>
    <div class="pills">
        <a class="pill {{ $status ? '' : 'active' }}" href="{{ route('content.messages.index') }}">All</a>
        @foreach (\App\Models\ContactMessage::STATUSES as $key => $label)
            <a class="pill {{ $status === $key ? 'active' : '' }}" href="{{ route('content.messages.index', ['status' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="card table-card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Received</th><th>From</th><th>Contact</th><th>Message</th><th>Status</th><th class="col-actions">Actions</th></tr></thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr>
                            <td>{{ $message->created_at->format('d M Y, H:i') }}</td>
                            <td class="strong">{{ $message->name }}</td>
                            <td>{{ $message->mobile }}@if ($message->email)<br><span class="muted small">{{ $message->email }}</span>@endif</td>
                            <td>@if ($message->subject)<strong>{{ $message->subject }}</strong><br>@endif{{ \Illuminate\Support\Str::limit($message->message, 160) }}</td>
                            <td><span class="status-badge status-{{ $message->status === 'new' ? 'pending' : 'active' }}">{{ \App\Models\ContactMessage::STATUSES[$message->status] ?? $message->status }}</span></td>
                            <td>
                                <div class="row-actions">
                                    @if ($message->status === 'new')
                                        <form method="POST" action="{{ route('content.messages.read', $message) }}" class="inline-form">@csrf @method('PUT')<button class="btn btn-small btn-light">Mark read</button></form>
                                    @endif
                                    <form method="POST" action="{{ route('content.messages.destroy', $message) }}" class="inline-form" onsubmit="return confirm('Delete this message?')">@csrf @method('DELETE')<button class="btn btn-small btn-danger">Delete</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-cell">No messages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $messages->links() }}</div>
    </div>
@endsection
