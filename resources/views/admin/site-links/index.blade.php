@extends('layouts.admin')

@section('title', 'Footer links')

@section('content')
    <h1 class="page-title">Menus &amp; footer links</h1>
    <p class="text-muted">The header menu, the header buttons, and the footer links shown on every public page. Add a link to a page only after that page exists.</p>

    <div class="card action-card">
        <h2>Add a link</h2>
        <form method="POST" action="{{ route('site-links.store') }}">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="group">Where *</label>
                    <select class="form-input" id="group" name="group" required>
                        @foreach (\App\Models\SiteLink::GROUPS as $key => $label)
                            <option value="{{ $key }}" @selected(old('group') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('group') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="label">Text *</label>
                    <input class="form-input" type="text" id="label" name="label" maxlength="60" value="{{ old('label') }}" required>
                    @error('label') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="url">Link *</label>
                    <input class="form-input" type="text" id="url" name="url" maxlength="255" placeholder="/apply or https://..." value="{{ old('url') }}" required>
                    @error('url') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="sort_order">Order (optional)</label>
                    <input class="form-input" type="number" id="sort_order" name="sort_order" min="0" max="9999" value="{{ old('sort_order') }}">
                    @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <label class="form-check"><input type="checkbox" name="is_active" value="1" checked><span>Show on the website</span></label>
            <label class="form-check"><input type="checkbox" name="new_tab" value="1"><span>Open in a new tab</span></label>
            <button type="submit" class="btn">Add link</button>
        </form>
    </div>

    @foreach (\App\Models\SiteLink::GROUPS as $key => $label)
        <div class="card action-card">
            <h2>{{ $label }}</h2>
            @if (! isset($links[$key]) || $links[$key]->isEmpty())
                <p class="empty-state">No links yet.</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Order</th><th>Text</th><th>Link</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($links[$key] as $link)
                                <tr>
                                    <td>{{ $link->sort_order }}</td>
                                    <td>{{ $link->label }}</td>
                                    <td class="text-muted">{{ $link->url }}</td>
                                    <td><span class="badge {{ $link->is_active ? 'badge-approved' : 'badge-deactivated' }}">{{ $link->is_active ? 'Shown' : 'Hidden' }}</span></td>
                                    <td>
                                        <div class="actions-row">
                                            <a href="{{ route('site-links.edit', $link) }}" class="btn btn-small btn-light">Edit</a>
                                            <form method="POST" action="{{ route('site-links.destroy', $link) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this link?');">Delete</button>
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
    @endforeach
@endsection
