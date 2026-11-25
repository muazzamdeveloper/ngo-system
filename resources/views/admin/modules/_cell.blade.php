{{-- One table cell. Expects $col (column definition) and $item (model). --}}
@php
    use App\Services\SiteMedia;
    use App\Services\SiteDocument;
    $value = $item->{$col['key']} ?? null;
    $format = $col['format'] ?? 'text';
    $sub = isset($col['sub']) ? ($item->{$col['sub']} ?? null) : null;
@endphp
@switch($format)
    @case('id')
        <span class="muted">#{{ $item->id }}</span>
        @break

    @case('image')
        @php $url = SiteMedia::url($value); @endphp
        @if ($url)
            <img class="thumb" src="{{ $url }}" alt="" loading="lazy">
        @else
            <span class="thumb thumb-empty" aria-label="No image">&mdash;</span>
        @endif
        @break

    @case('pdf')
        <span class="pdf-badge">PDF</span>
        @break

    @case('strong')
        @php $titleHtml = e($value); @endphp
        @if (($col['link'] ?? null) === 'file')
            @php $fileUrl = SiteDocument::url($item->file_path); @endphp
            @if ($fileUrl)
                <a class="strong" href="{{ $fileUrl }}" target="_blank" rel="noopener">{{ $value }}</a>
            @else
                <span class="strong">{{ $value }}</span>
            @endif
        @else
            <span class="strong">{{ $value }}</span>
        @endif
        @if ($sub)
            <div class="muted small">{{ $sub }}</div>
        @endif
        @break

    @case('download')
        <a class="btn btn-small" href="{{ route($col['route'], $item) }}" aria-label="Download PDF for {{ $item->member_name ?? 'this record' }}">Download PDF</a>
        @break

    @case('status')
        <span class="status-badge status-{{ $value }}">{{ ucfirst((string) $value) }}</span>
        @break

    @case('badge')
        <span class="tag">{{ $value }}</span>
        @break

    @case('date')
        @php $d = $value ? \Illuminate\Support\Carbon::parse($value) : null; @endphp
        {{ $d ? $d->format('d M Y') : '—' }}
        @break

    @case('eventdate')
        @php
            $d = $value ? \Illuminate\Support\Carbon::parse($value) : null;
            $t = $item->event_time ? \Illuminate\Support\Carbon::parse($item->event_time)->format('h:i A') : null;
        @endphp
        <strong>{{ $d?->format('d M Y') }}</strong>
        @if ($t)<div class="muted small">{{ $t }}</div>@endif
        @break

    @case('range')
        @php
            $from = $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : '—';
            $toValue = $item->{$col['to']} ?? null;
            $to = $toValue ? \Illuminate\Support\Carbon::parse($toValue)->format('d M Y') : 'Ongoing';
        @endphp
        <span class="small">{{ $from }} &rarr; {{ $to }}</span>
        @break

    @case('money')
        <span class="money">&#8377;{{ number_format((float) $value, 2) }}</span>
        @break

    @case('goal')
        <div class="money">Goal &#8377;{{ number_format((float) $item->goal_amount, 0) }}</div>
        <div class="muted small">Raised &#8377;{{ number_format((float) $item->raised, 0) }} ({{ $item->progress }}%)</div>
        <div class="progress" aria-hidden="true"><span style="width: {{ $item->progress }}%"></span></div>
        @break

    @case('stars')
        <span class="stars" aria-label="{{ (int) $value }} out of 5">{{ str_repeat('★', (int) $value) }}<span class="muted">{{ str_repeat('★', 5 - (int) $value) }}</span></span>
        @break

    @case('link')
        @if ($value)
            <a href="{{ $value }}" target="_blank" rel="noopener" class="small">{{ \Illuminate\Support\Str::limit($value, 40) }}</a>
        @else
            <span class="muted">—</span>
        @endif
        @break

    @case('excerpt')
        <span class="small">{{ \Illuminate\Support\Str::limit((string) $value, 90) }}</span>
        @break

    @case('upper')
        <span class="strong">{{ strtoupper((string) $value) }}</span>
        @break

    @case('validity')
        {{ $item->validityLabel() }}
        @break

    @default
        {{ filled($value) ? $value : '—' }}
        @if ($sub)<div class="muted small">{{ $sub }}</div>@endif
@endswitch
