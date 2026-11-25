@extends('layouts.admin')

@section('title', 'News ticker')

@section('content')
    <h1 class="page-title">News ticker</h1>
    <p class="text-muted">These headlines slide from right to left at the top of every public page. With no active headline the ticker is hidden.</p>

    <div class="card action-card">
        <h2>Posts in the ticker</h2>
        <form method="POST" action="{{ route('ticker.settings') }}" class="filters">
            @csrf
            <label for="ticker_posts" class="form-label" style="align-self: center; margin: 0;">Also show the newest</label>
            <input class="form-input" type="number" id="ticker_posts" name="ticker_posts" min="0" max="10" style="flex: 0 0 90px;"
                   value="{{ old('ticker_posts', \App\Models\OrganizationSetting::current()->ticker_posts) }}">
            <span style="align-self: center;">published post titles after the headlines below (0 = none)</span>
            <button type="submit" class="btn">Save</button>
        </form>
        @error('ticker_posts') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="card action-card">
        <h2>Add a headline</h2>
        <form method="POST" action="{{ route('ticker.store') }}">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="text">Headline *</label>
                    <input class="form-input" type="text" id="text" name="text" maxlength="160" value="{{ old('text') }}" required>
                    @error('text') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="url">Link (optional)</label>
                    <input class="form-input" type="text" id="url" name="url" maxlength="255" placeholder="/apply or https://..." value="{{ old('url') }}">
                    @error('url') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="sort_order">Order (optional)</label>
                    <input class="form-input" type="number" id="sort_order" name="sort_order" min="0" max="9999" value="{{ old('sort_order') }}">
                    @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-check" style="margin-top: 1.8rem;"><input type="checkbox" name="is_active" value="1" checked><span>Show on the website</span></label>
                </div>
            </div>
            <button type="submit" class="btn">Add headline</button>
        </form>
    </div>

    <div class="card action-card">
        <h2>Headlines</h2>
        @if ($items->isEmpty())
            <p class="empty-state">No headlines yet.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Order</th><th>Headline</th><th>Link</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->sort_order }}</td>
                                <td>{{ $item->text }}</td>
                                <td class="text-muted">{{ $item->url ?? '-' }}</td>
                                <td><span class="badge {{ $item->is_active ? 'badge-approved' : 'badge-deactivated' }}">{{ $item->is_active ? 'Shown' : 'Hidden' }}</span></td>
                                <td>
                                    <div class="actions-row">
                                        <a href="{{ route('ticker.edit', $item) }}" class="btn btn-small btn-light">Edit</a>
                                        <form method="POST" action="{{ route('ticker.destroy', $item) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this headline?');">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
