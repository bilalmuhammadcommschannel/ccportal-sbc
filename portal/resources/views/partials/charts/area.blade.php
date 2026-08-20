{{-- Area sparkline for CPU/RAM history. Params: $points (0..$max ints), $max, $color, $id --}}
@php
    $pts = array_values(array_map('floatval', $points ?? []));
    $max = $max ?? 100;
    if (count($pts) < 2) { $pts = [$pts[0] ?? 0, $pts[0] ?? 0]; }
    $n = count($pts); $W = 320; $H = 76; $step = $W / ($n - 1);
    $coords = [];
    foreach ($pts as $i => $v) {
        $x = round($i * $step, 1);
        $y = round($H - (min($max, max(0, $v)) / $max) * ($H - 6) - 3, 1);
        $coords[] = "$x,$y";
    }
    $line = implode(' ', $coords);
    $area = "0,{$H} " . $line . " {$W},{$H}";
    $col = $color ?? 'var(--brand-2)';
    $gid = 'ag_' . ($id ?? substr(md5($line), 0, 6));
@endphp
<svg class="chart-svg" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="none" style="height:76px" role="img">
    <defs>
        <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="{{ $col }}" stop-opacity="0.28"/>
            <stop offset="100%" stop-color="{{ $col }}" stop-opacity="0.02"/>
        </linearGradient>
    </defs>
    <polygon points="{{ $area }}" fill="url(#{{ $gid }})"/>
    <polyline points="{{ $line }}" fill="none" stroke="{{ $col }}" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
</svg>
