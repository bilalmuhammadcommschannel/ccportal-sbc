{{-- Donut chart. Params: $slices=[['label','value','color'],...], $size, $thick, $centerVal, $centerLabel --}}
@php
    $size = $size ?? 156; $thick = $thick ?? 24;
    $c = $size / 2; $r = $c - $thick / 2; $circ = 2 * M_PI * $r;
    $total = array_sum(array_map(fn ($s) => (float) $s['value'], $slices));
    $offset = 0.0;
@endphp
<svg class="chart-svg" viewBox="0 0 {{ $size }} {{ $size }}" style="max-width:{{ $size }}px;margin:0 auto" role="img">
    <circle cx="{{ $c }}" cy="{{ $c }}" r="{{ $r }}" fill="none" stroke="var(--line)" stroke-width="{{ $thick }}"/>
    @if($total > 0)
        @foreach($slices as $s)
            @php $v = (float) $s['value']; @endphp
            @if($v > 0)
                @php $len = ($v / $total) * $circ; @endphp
                <circle cx="{{ $c }}" cy="{{ $c }}" r="{{ $r }}" fill="none" stroke="{{ $s['color'] }}" stroke-width="{{ $thick }}"
                    stroke-dasharray="{{ round($len, 2) }} {{ round($circ - $len, 2) }}"
                    stroke-dashoffset="{{ round(-$offset, 2) }}"
                    transform="rotate(-90 {{ $c }} {{ $c }})">
                    <title>{{ $s['label'] }}: {{ (int) $v }}</title>
                </circle>
                @php $offset += $len; @endphp
            @endif
        @endforeach
    @endif
    <text x="{{ $c }}" y="{{ $c - 1 }}" text-anchor="middle" font-size="26" font-weight="800" fill="var(--text)" style="font-variant-numeric:tabular-nums">{{ $centerVal ?? (int) $total }}</text>
    <text x="{{ $c }}" y="{{ $c + 16 }}" text-anchor="middle" font-size="9.5" fill="var(--muted)" letter-spacing="1.2">{{ strtoupper($centerLabel ?? 'TOTAL') }}</text>
</svg>
