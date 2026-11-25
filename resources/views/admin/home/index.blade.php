@extends('layouts.admin')

@section('title', 'Home page')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Home page</h1>
        <a href="{{ url('/') }}" class="btn btn-light" target="_blank" rel="noopener">View home page</a>
    </div>
    <p class="text-muted">The home page is built from sections, shown in the order below. Add, edit, hide or delete them. A smaller order number appears higher.</p>

    <div class="card action-card">
        <h2>Add a section</h2>
        <form method="POST" action="{{ route('home-sections.store') }}" class="filters">
            @csrf
            <select class="form-input" name="type" required style="flex: 1 1 260px;">
                @foreach (\App\Models\HomeSection::TYPES as $key => $type)
                    <option value="{{ $key }}">{{ $type['label'] }} - {{ $type['help'] }}</option>
                @endforeach
            </select>
            <input class="form-input" type="text" name="title" maxlength="160" placeholder="Heading (optional)" style="flex: 1 1 200px;">
            <button type="submit" class="btn">Add section</button>
        </form>
        @error('type') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="card action-card">
        <h2>Sections</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Order</th><th>Section</th><th>Type</th><th>Items</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($sections as $section)
                        <tr>
                            <td>{{ $section->sort_order }}</td>
                            <td><strong>{{ $section->title ?: '(no heading)' }}</strong></td>
                            <td class="text-muted">{{ $section->definition()['label'] }}</td>
                            <td>{{ $section->definition()['item'] === [] ? '-' : $section->items_count }}</td>
                            <td><span class="badge {{ $section->is_active ? 'badge-approved' : 'badge-deactivated' }}">{{ $section->is_active ? 'Shown' : 'Hidden' }}</span></td>
                            <td>
                                <div class="actions-row">
                                    <a href="{{ route('home-sections.edit', $section) }}" class="btn btn-small btn-light">Edit</a>
                                    <form method="POST" action="{{ route('home-sections.destroy', $section) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this section and everything in it?');">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
