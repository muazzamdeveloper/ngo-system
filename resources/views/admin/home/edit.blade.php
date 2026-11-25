@extends('layouts.admin')

@section('title', 'Edit section')

@section('content')
    @php($def = $section->definition())

    <div class="page-header">
        <h1 class="page-title">{{ $def['label'] }}</h1>
        <a href="{{ route('home-sections.index') }}" class="btn btn-light">Back to home page sections</a>
    </div>
    <p class="text-muted">{{ $def['help'] }}</p>

    <div class="card action-card">
        <h2>Section</h2>
        <form method="POST" action="{{ route('home-sections.update', $section) }}" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="form-grid">
                @if ($section->hasField('title'))
                    <div class="form-group">
                        <label class="form-label" for="title">Heading</label>
                        <input class="form-input" type="text" id="title" name="title" maxlength="160" value="{{ old('title', $section->title) }}">
                        @error('title') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                @endif

                @if ($section->hasField('subtitle'))
                    <div class="form-group">
                        <label class="form-label" for="subtitle">{{ $section->type === 'message' ? 'Name and role (under the signature)' : 'Small text under the heading' }}</label>
                        <input class="form-input" type="text" id="subtitle" name="subtitle" maxlength="255" value="{{ old('subtitle', $section->subtitle) }}">
                        @error('subtitle') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                @endif

                @if ($section->hasField('body'))
                    <div class="form-group form-grid-full">
                        <label class="form-label" for="body">Text</label>
                        <textarea class="form-input" id="body" name="body" rows="6" maxlength="6000">{{ old('body', $section->body) }}</textarea>
                        <div class="form-hint">Blank lines start a new paragraph. You can use **bold** and *italic*.</div>
                        @error('body') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                @endif

                @if ($section->hasField('image'))
                    @include('admin.partials.image-field', ['field' => 'image', 'label' => $section->type === 'message' ? 'Photo' : 'Picture', 'url' => $section->imageUrl(), 'remove' => 'remove_image'])
                @endif

                @if ($section->hasField('image2'))
                    @include('admin.partials.image-field', ['field' => 'image2', 'label' => 'Signature picture', 'url' => $section->image2Url(), 'remove' => 'remove_image2'])
                @endif

                @if ($section->hasField('link'))
                    <div class="form-group">
                        <label class="form-label" for="link_label">Button text</label>
                        <input class="form-input" type="text" id="link_label" name="link_label" maxlength="60" value="{{ old('link_label', $section->link_label) }}">
                        @error('link_label') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="link_url">Button link</label>
                        <input class="form-input" type="text" id="link_url" name="link_url" maxlength="255" placeholder="/apply or https://..." value="{{ old('link_url', $section->link_url) }}">
                        @error('link_url') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                @endif

                @if ($section->hasField('count'))
                    <div class="form-group">
                        <label class="form-label" for="item_limit">How many posts to show (1 to 12)</label>
                        <input class="form-input" type="number" id="item_limit" name="item_limit" min="1" max="12" value="{{ old('item_limit', $section->item_limit) }}">
                        @error('item_limit') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                @endif

                @if ($section->hasField('map'))
                    <div class="form-group form-grid-full">
                        <label class="form-label" for="map">Map address (optional)</label>
                        <input class="form-input" type="text" id="map" name="map" maxlength="700" placeholder="https://www.google.com/maps/embed?pb=..." value="{{ old('map', $section->link_url) }}">
                        <div class="form-hint">In Google Maps: Share, then "Embed a map", and copy only the address inside src="...".</div>
                        @error('map') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label" for="sort_order">Order on the page (smaller comes first)</label>
                    <input class="form-input" type="number" id="sort_order" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $section->sort_order) }}">
                    @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <label class="form-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $section->is_active))><span>Show this section on the home page</span></label>

            <div class="form-actions"><button type="submit" class="btn">Save section</button></div>
        </form>
    </div>

    @if ($def['item'] !== [])
        <div class="card action-card">
            <h2>{{ $def['item_name'] }}s</h2>

            @if ($section->items->isEmpty())
                <p class="empty-state">Nothing here yet. Add the first {{ strtolower($def['item_name']) }} below.</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Order</th><th></th><th>{{ $def['item_name'] }}</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($section->items as $item)
                                <tr>
                                    <td>{{ $item->sort_order }}</td>
                                    <td>@if ($item->imageUrl())<img src="{{ $item->imageUrl() }}" alt="" style="width: 64px; height: 44px; object-fit: cover; border-radius: 6px;">@endif</td>
                                    <td><strong>{{ $item->title ?: '(picture only)' }}</strong>@if ($item->subtitle)<br><span class="text-muted">{{ $item->subtitle }}</span>@endif</td>
                                    <td><span class="badge {{ $item->is_active ? 'badge-approved' : 'badge-deactivated' }}">{{ $item->is_active ? 'Shown' : 'Hidden' }}</span></td>
                                    <td>
                                        <div class="actions-row">
                                            <a href="{{ route('home-items.edit', $item) }}" class="btn btn-small btn-light">Edit</a>
                                            <form method="POST" action="{{ route('home-items.destroy', $item) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this?');">Delete</button>
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

        <div class="card action-card">
            <h2>Add a {{ strtolower($def['item_name']) }}</h2>
            <form method="POST" action="{{ route('home-items.store', $section) }}" enctype="multipart/form-data">
                @csrf
                @include('admin.home._item_fields', ['section' => $section, 'item' => null])
                <div class="form-actions"><button type="submit" class="btn">Add</button></div>
            </form>
        </div>
    @endif
@endsection
