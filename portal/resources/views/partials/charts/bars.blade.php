{{-- Hour-of-day bar chart ("signal ridge"). Params: $values (24 ints, hour=>count) --}}
@php
    $vals = [];
    for ($i = 0; $i < 24; $i++) { $vals[$i] = (int) ($values[$i] ?? 0); }
    $n = 24; $max = max(1, max($vals));
    $peakHour = array_search(max($vals), $vals);
    $W = 720; $H = 168; $padX = 10; $padTop = 22; $padBot = 22;
    $plotH = $H - $padTop - $padBot;
    $slot = ($W - $padX * 2) / $n;
    $bw = $slot * 0.62;
@endphp
<svg class="chart-svg" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Calls by hour">
    {{-- baseline --}}
    <line x1="{{ $padX }}" y1="{{ $padTop + $plotH }}" x2="{{ $W - $padX }}" y2="{{ $padTop + $plotH }}" stroke="var(--line)" stroke-width="1"/>
    @foreach($vals as $h => $v)
        @php
            $x = $padX + $h * $slot + ($slot - $bw) / 2;
            $bh = $v > 0 ? max(2, ($v / $max) * $plotH) : 0;
            $y = $padTop + $plotH - $bh;
            $isPeak = ($h === $peakHour && $v > 0);
        @endphp
        @if($v > 0)
            <rect x="{{ round($x, 1) }}" y="{{ round($y, 1) }}" width="{{ round($bw, 1) }}" height="{{ round($bh, 1) }}"
                  rx="2.5" fill="{{ $isPeak ? 'var(--brand)' : 'var(--brand-2)' }}" opacity="{{ $isPeak ? 1 : 0.82 }}">
                <title>{{ sprintf('%02d:00', $h) }} — {{ $v }} calls</title>
            </rect>
            @if($isPeak)
                <text x="{{ round($x + $bw / 2, 1) }}" y="{{ round($y - 6, 1) }}" text-anchor="middle" font-size="11" font-weight="800" fill="var(--brand)">{{ $v }}</text>
            @endif
        @endif
        @if($h % 3 === 0)
            <text x="{{ round($padX + $h * $slot + $slot / 2, 1) }}" y="{{ $H - 6 }}" text-anchor="middle" font-size="10" fill="var(--muted)">{{ sprintf('%02d', $h) }}</text>
        @endif
    @endforeach
</svg>
