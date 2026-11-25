@extends('layouts.admin')

@section('title', 'Template management')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Template &amp; certificate assets</h1>
            <p class="page-subtitle">Logo, signature, seal and templates used when documents are issued. PNG or JPG, up to 5 MB.</p>
        </div>
    </div>

    @if ($missingRequired->isNotEmpty())
        <div class="notice notice-warning">
            Required before issuing certificates or letters: {{ $missingRequired->map(fn ($k) => \App\Models\TemplateAsset::ASSETS[$k][0])->implode(', ') }}.
        </div>
    @else
        <div class="notice notice-success">All required files are present.</div>
    @endif

    <div class="asset-grid">
        @foreach ($definitions as $key => [$label, $required])
            @php $asset = $assets->get($key); $url = $asset ? \App\Services\SiteMedia::url($asset->path) : null; @endphp
            <div class="card asset-card">
                <div class="card-head">
                    <h2 class="card-title">{{ $label }}</h2>
                    @if ($required)<span class="tag">Required</span>@endif
                </div>
                <div class="asset-preview">
                    @if ($url)
                        <img src="{{ $url }}" alt="{{ $label }} preview">
                    @else
                        <span class="muted">No file uploaded yet</span>
                    @endif
                </div>
                @if ($asset)
                    <div class="muted small">{{ $asset->original_name }} &middot; updated {{ $asset->updated_at?->format('d M Y, h:i A') }}</div>
                @endif
                <form method="POST" action="{{ route('templates.upload', $key) }}" enctype="multipart/form-data" class="asset-form">
                    @csrf
                    <input class="form-input file-input" type="file" name="file" accept="image/png,image/jpeg" required aria-label="{{ $label }} file">
                    <button type="submit" class="btn btn-small">Upload</button>
                </form>
                @if ($url)
                    <div class="row-actions">
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-small btn-light">View</a>
                        <form method="POST" action="{{ route('templates.destroy', $key) }}" onsubmit="return confirm('Remove this file?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-small btn-danger">Remove</button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endsection
