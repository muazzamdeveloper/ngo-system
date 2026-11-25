@extends('layouts.admin')
@section('title', 'Edit: ' . $title)
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit: {{ $title }}</h1>
            <p class="page-subtitle">Change any text or image below, then save. Until you save, the default text is shown.</p>
        </div>
        <a href="{{ url('/page/' . $page) }}" target="_blank" rel="noopener" class="btn btn-light">View page</a>
    </div>

    <form method="POST" action="{{ route('content.update', $page) }}" enctype="multipart/form-data" class="card form-card">
        @csrf @method('PUT')
        @foreach ($sections as $section)
        @if ($section['label'] !== '')
            <h3 class="section-title">{{ $section['label'] }}</h3>
        @endif
        @foreach ($section['blocks'] as $key => $block)
            <div class="form-group span-all">
                <label class="form-label" for="block-{{ $key }}">{{ $block['label'] }}</label>
                @if ($block['type'] === 'text')
                    <input id="block-{{ $key }}" class="form-input" type="text" name="blocks[{{ $key }}]" value="{{ $block['value'] }}" maxlength="500">
                @elseif ($block['type'] === 'richtext')
                    @include('admin.partials.rich-text', ['name' => 'blocks[' . $key . ']', 'value' => \App\Support\HtmlCleaner::clean((string) $block['value']), 'label' => $block['label']])
                @elseif ($block['type'] === 'image')
                    <div class="current-file">
                        @if ($block['image_url'])
                            <img class="thumb-large" src="{{ $block['image_url'] }}" alt="">
                            <label class="switch-row"><input type="checkbox" name="remove[{{ $key }}]" value="1"> Remove image</label>
                        @else
                            <span class="muted">No image uploaded yet.</span>
                        @endif
                    </div>
                    <input id="block-{{ $key }}" class="form-input file-input" type="file" name="images[{{ $key }}]" accept="image/jpeg,image/png,image/webp">
                    <span class="form-hint">JPG, PNG or WEBP, up to 5 MB.</span>
                @endif
            </div>
        @endforeach
        @endforeach

        <div class="form-actions">
            <button type="submit" class="btn">Save page</button>
            <a href="{{ route('content.index') }}" class="btn btn-light">Back</a>
        </div>
    </form>
@endsection
