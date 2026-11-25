{{-- One form field. Expects $field (definition) and $item (model, may be new). --}}
@php
    use App\Services\SiteMedia;
    use App\Services\SiteDocument;
    $name = $field['name'];
    $column = $field['column'] ?? $name;
    $raw = $item->{$column} ?? null;
    if ($raw instanceof \DateTimeInterface) {
        $raw = $raw->format($field['type'] === 'date' ? 'Y-m-d' : 'Y-m-d');
    }
    if ($field['type'] === 'time' && is_string($raw)) {
        $raw = substr($raw, 0, 5);
    }
    if ($field['type'] === 'date' && is_string($raw) && $raw !== '') {
        $raw = substr($raw, 0, 10);
    }
    $value = old($name, $raw);
    $wide = ($field['col'] ?? 1) === 2 || in_array($field['type'], ['richtext', 'textarea'], true);
    $required = ! empty($field['required']);
    $id = 'f-' . $name;
@endphp

<div class="form-group {{ $wide ? 'span-2' : '' }}">
    <label class="form-label" for="{{ $id }}">{{ $field['label'] }} @if ($required)<span class="req">*</span>@endif</label>

    @switch($field['type'])
        @case('textarea')
            <textarea class="form-input" id="{{ $id }}" name="{{ $name }}" rows="{{ $field['rows'] ?? 4 }}" @if(!empty($field['max'])) maxlength="{{ $field['max'] }}" @endif @if($required) required @endif>{{ $value }}</textarea>
            @break

        @case('richtext')
            @include('admin.partials.rich-text', ['name' => $name, 'value' => \App\Support\HtmlCleaner::clean((string) $value), 'label' => $field['label']])
            @break

        @case('select')
            <select class="form-input" id="{{ $id }}" name="{{ $name }}" @if($required) required @endif>
                <option value="">— Select —</option>
                @foreach ($field['options'] as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>
            @break

        @case('status')
            <select class="form-input" id="{{ $id }}" name="{{ $name }}" required>
                @foreach ((defined(get_class($item).'::STATUSES') ? constant(get_class($item).'::STATUSES') : ['active' => 'Active', 'inactive' => 'Inactive']) as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected((string) ($value ?: 'active') === $statusValue)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            @break

        @case('money')
            <div class="input-prefix">
                <span>&#8377;</span>
                <input class="form-input" type="number" step="0.01" min="{{ $field['min'] ?? 0 }}" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif>
            </div>
            @break

        @case('number')
            <input class="form-input" type="number" step="1" min="{{ $field['min'] ?? 0 }}" @if(isset($field['max'])) max="{{ $field['max'] }}" @endif id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif>
            @break

        @case('date')
            <input class="form-input" type="date" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif>
            @break

        @case('time')
            <input class="form-input" type="time" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}">
            @break

        @case('url')
        @case('youtube')
            <input class="form-input" type="url" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 500 }}" placeholder="{{ $field['type'] === 'youtube' ? 'https://www.youtube.com/watch?v=…' : 'https://' }}" @if($required) required @endif>
            @break

        @case('image')
            @php $current = SiteMedia::url($item->{$column} ?? null); @endphp
            @if ($current)
                <div class="current-file"><img src="{{ $current }}" alt="Current image" class="thumb-large"><span class="muted small">Current image. Upload a new one to replace it.</span></div>
            @endif
            <input class="form-input file-input" type="file" id="{{ $id }}" name="{{ $name }}" accept="image/png,image/jpeg,image/webp" @if($required && ! $current) required @endif>
            @break

        @case('pdf')
            @php $currentPdf = SiteDocument::url($item->{$column} ?? null); @endphp
            @if ($currentPdf)
                <div class="current-file"><a href="{{ $currentPdf }}" target="_blank" rel="noopener">View current PDF</a></div>
            @endif
            <input class="form-input file-input" type="file" id="{{ $id }}" name="{{ $name }}" accept="application/pdf" @if($required && ! $currentPdf) required @endif>
            @break

        @default
            <input class="form-input" type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @if(!empty($field['max'])) maxlength="{{ $field['max'] }}" @endif @if(!empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif @if($required) required @endif>
    @endswitch

    @if (! empty($field['hint']))
        <div class="form-hint">{{ $field['hint'] }}</div>
    @endif
    @error($name) <div class="form-error">{{ $message }}</div> @enderror
</div>
