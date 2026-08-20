@extends('layouts.app')
@section('title', 'Dashboard')
@section('topbar-right')
    @php $ccStale = $stats['stale'] ?? true; @endphp
    <span class="muted" id="t-age" style="font-size:12px;font-weight:500">{{ $ccStale ? '⚠ snapshot stale' : '● live · updated '.($stats['age_sec'] ?? '?').'s ago' }}</span>
@endsection
@section('content')
@php
    // Fixed jail -> colour map so the donut, legend and Firewall page agree.
    $jailColor = [
        'ccsip-scanner' => '#2E7BC4', 'ccsip-auth' => '#59a6e0',
        'ccportal-probe' => '#b57500', 'ccportal-auth' => '#7c5cc4', 'sshd' => '#64748b',
    ];
    $liveCalls = (int) data_get($stats, 'freeswitch.calls', 0);
    $cpu = (int) data_get($stats, 'host.cpu_pct', 0);
    $mem = (int) data_get($stats, 'host.mem_pct', 0);
    $cpuHist = array_map(fn ($s) => (int) ($s['cpu'] ?? 0), $history);
    $memHist = array_map(fn ($s) => (int) ($s['mem'] ?? 0), $history);
    $bannedNow = (int) data_get($stats, 'fail2ban.currently_banned', 0);
    // donut slices: currently-banned per jail
    $slices = collect(data_get($stats, 'fail2ban.jails', []))
        ->filter(fn ($j) => ($j['currently_banned'] ?? 0) > 0)
        ->map(fn ($j) => ['label' => $j['name'], 'value' => (int) $j['currently_banned'], 'color' => $jailColor[$j['name']] ?? '#9db2c6'])
        ->sortByDesc('value')->values()->all();
    $peakCalls = max($callHours ?: [0]);
@endphp

    @if($stats['stale'] ?? true)
        <div class="errs">Monitoring snapshot is stale ({{ $stats['age_sec'] ?? '?' }}s old) — check <code>cc-stats.timer</code> on the server.</div>
    @endif

    {{-- ===== KPI cards (each links to its section) ===== --}}
    <div class="kpis">
        <a class="kpi hero" href="{{ route('cdrs.index') }}">
            <span class="go">→</span>
            <div class="k-label">Live calls</div>
            <div class="k-val">@if($liveCalls>0)<span class="live-dot"></span>@endif<span id="t-calls">{{ $liveCalls }}</span></div>
            <div class="k-sub"><span id="t-chans">{{ data_get($stats,'freeswitch.channels',0) }}</span> channels
                @if(!data_get($stats,'freeswitch.up'))· <span class="pill off">switch down</span>@endif</div>
        </a>

        <a class="kpi" href="{{ route('cdrs.index') }}">
            <span class="go">→</span>
            <div class="k-label">Calls today</div>
            <div class="k-val">{{ number_format($traffic['calls']) }}</div>
            <div class="k-sub">{{ $traffic['minutes'] }} billed min</div>
        </a>

        <a class="kpi" href="{{ route('cdrs.index') }}">
            <span class="go">→</span>
            <div class="k-label">Charged today</div>
            <div class="k-val">${{ number_format((float) $traffic['cost'], 2) }}</div>
            <div class="k-sub">customer charges</div>
        </a>

        @if($isAdmin)
        <a class="kpi money" href="{{ route('cdrs.index') }}">
            <span class="go">→</span>
            <div class="k-label">Profit · today</div>
            <div class="k-val">${{ number_format($profit['today'], 2) }}</div>
            <div class="k-sub">${{ number_format($profit['month'], 2) }} month · est. @ {{ $profit['margin_pct'] }}% margin</div>
        </a>

        <a class="kpi sec {{ $bannedNow>0 ? '' : 'clean' }}" href="{{ route('firewall.index') }}">
            <span class="go">→</span>
            <div class="k-label">Blocked IPs</div>
            <div class="k-val" id="t-banned">{{ number_format($bannedNow) }}</div>
            <div class="k-sub"><span id="t-attacks">{{ number_format(data_get($stats,'fail2ban.total_failed',0)) }}</span> attempts blocked · manage →</div>
        </a>
        @endif
    </div>

    {{-- ===== peak calls + system health ===== --}}
    @if($isAdmin)
    <div class="cols wide">
        <a class="card" href="{{ route('cdrs.index') }}" style="color:inherit">
            <div class="rowbar"><h2 style="margin:0">Peak calls · today</h2><span class="muted" style="font-size:12px">AEST · peak {{ $peakCalls }}/h</span></div>
            @include('partials.charts.bars', ['values' => $callHours])
        </a>
        <div class="card">
            <h2>System health</h2>
            <div class="health">
                <div class="gauge">
                    <div class="g-top"><span class="eyebrow">CPU</span><span class="g-val" id="g-cpu">{{ $cpu }}%</span></div>
                    @include('partials.charts.area', ['points' => $cpuHist ?: [$cpu], 'color' => 'var(--brand-2)', 'id' => 'cpu'])
                </div>
                <div class="gauge">
                    <div class="g-top"><span class="eyebrow">Memory</span><span class="g-val" id="g-mem">{{ $mem }}%</span></div>
                    @include('partials.charts.area', ['points' => $memHist ?: [$mem], 'color' => '#12855a', 'id' => 'mem'])
                </div>
            </div>
            <table class="kv" style="margin-top:12px">
                <tr><th>Load avg</th><td>{{ data_get($stats,'host.load','?') }}</td></tr>
                <tr><th>Memory</th><td>{{ number_format(data_get($stats,'host.mem_available_mb',0)) }} MB free / {{ number_format(data_get($stats,'host.mem_total_mb',0)) }} MB</td></tr>
                <tr><th>Disk</th><td>{{ data_get($stats,'host.disk_used_pct',0) }}% used</td></tr>
                <tr><th>Uptime</th><td>{{ round(data_get($stats,'host.uptime_sec',0)/3600, 1) }} h</td></tr>
            </table>
        </div>
    </div>
    @else
    <div class="card">
        <div class="rowbar"><h2 style="margin:0">Peak calls · today</h2><span class="muted" style="font-size:12px">AEST · peak {{ $peakCalls }}/h</span></div>
        @include('partials.charts.bars', ['values' => $callHours])
    </div>
    @endif

    {{-- ===== live calls (admin: full channel detail) ===== --}}
    @if($isAdmin)
    <div class="card">
        <div class="rowbar"><h2 style="margin:0">Live calls</h2><span class="muted" style="font-size:12px" id="t-age2">{{ $liveCalls }} in progress</span></div>
        <div style="overflow-x:auto">
            <table id="live-table">
                <thead><tr><th>Account</th><th>Trunk</th><th>Caller ID</th><th>Destination</th><th>Direction</th><th>Codec</th></tr></thead>
                <tbody>
                @forelse(data_get($stats,'freeswitch.channel_list',[]) as $c)
                    @php $dir = $c['direction'] ?? ''; @endphp
                    <tr>
                        <td>{{ $c['account_label'] ?? '—' }}</td>
                        <td class="muted">{{ $c['trunk_label'] ?? 'Endpoint' }}</td>
                        <td class="num">{{ $c['cid'] ?? '' }}</td>
                        <td class="num">{{ $c['dest'] ?? '' }}</td>
                        <td>@if($dir==='inbound')<span class="pill dir-in">▼ inbound</span>@elseif($dir==='outbound')<span class="pill dir-out">▲ outbound</span>@else{{ $dir }}@endif</td>
                        <td class="muted">{{ $c['codec'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No calls in progress.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ===== firewall overview (donut) + services ===== --}}
    @if($isAdmin)
    <div class="cols">
        <div class="card">
            <div class="rowbar"><h2 style="margin:0">Firewall · blocked now</h2><a class="btn ghost sm" href="{{ route('firewall.index') }}">Manage →</a></div>
            <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap">
                <div style="flex:0 0 auto">
                    @include('partials.charts.donut', ['slices' => $slices, 'centerVal' => number_format($bannedNow), 'centerLabel' => 'banned'])
                </div>
                <div class="legend" style="flex:1;min-width:170px;flex-direction:column">
                    @forelse($slices as $s)
                        <div class="lk"><span class="sw" style="background:{{ $s['color'] }}"></span>{{ $s['label'] }} <b style="margin-left:auto">{{ number_format($s['value']) }}</b></div>
                    @empty
                        <div class="muted">Nothing currently blocked.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Services</h2>
            <div class="svc-list">
                @foreach(data_get($stats,'services',[]) as $s)
                    @php
                        $state = $s['state'] ?? 'unknown';
                        $lc = $state === 'active' ? 'on' : ($state === 'unknown' ? 'unk' : 'off');
                    @endphp
                    <div class="svc-row">
                        <span class="light {{ $lc }}" title="{{ $state }}"></span>
                        <b>{{ str_replace(['.timer','.service'],'',$s['name']) }}</b>
                        <span class="st muted">{{ $state }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ===== low balances + platform inventory ===== --}}
    <div class="cols">
        <div class="card">
            <div class="rowbar"><h2 style="margin:0">Prepaid balances running low</h2><a class="muted" style="font-size:12px" href="{{ route('balances.index') }}">All balances →</a></div>
            <table>
                <thead><tr><th>Account</th><th class="right">Balance</th><th></th></tr></thead>
                <tbody>
                @forelse($lowBalance as $b)
                    <tr>
                        <td>{{ $b->company_name ?? $b->account_id }}<br><span class="muted mono" style="font-size:12px">{{ $b->account_id }}</span></td>
                        <td class="right num" style="color:{{ (float)$b->balance <= 0 ? 'var(--bad)' : 'inherit' }}">{{ number_format((float)$b->balance, 4) }}</td>
                        <td class="right"><a class="btn ghost sm" href="{{ route('balances.show', $b->account_id) }}">Top up</a></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">No prepaid accounts below threshold.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Platform</h2>
            <table>
                <tr><th>Customers</th><td class="num">{{ number_format($counts['customers']) }}</td><td class="right"><a class="muted" href="{{ route('customers.index') }}">view →</a></td></tr>
                <tr><th>Resellers</th><td class="num">{{ number_format($counts['resellers']) }}</td><td class="right"><a class="muted" href="{{ route('resellers.index') }}">view →</a></td></tr>
                <tr><th>SIP endpoints</th><td class="num">{{ number_format($counts['endpoints']) }}</td><td class="right"><a class="muted" href="{{ route('endpoints.index') }}">view →</a></td></tr>
                <tr><th>DIDs</th><td class="num">{{ number_format($counts['dids']) }}</td><td class="right"><a class="muted" href="{{ route('dids.index') }}">view →</a></td></tr>
                @if($isAdmin)<tr><th>Carriers</th><td class="num">{{ number_format($counts['carriers']) }}</td><td class="right"><a class="muted" href="{{ route('carriers.index') }}">view →</a></td></tr>@endif
            </table>
        </div>
    </div>

    <script>
    // Poll the live endpoint so calls/bans/health update without a page reload.
    (function () {
        var url = @json(route('dashboard.live'));
        function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
        function set(id,v){ var e=document.getElementById(id); if(e) e.textContent=v; }
        function tick() {
            fetch(url, {headers:{'Accept':'application/json'}, credentials:'same-origin'})
              .then(function(r){ return r.ok ? r.json() : null; })
              .then(function(d){
                if (!d) return;
                set('t-calls', d.calls); set('t-chans', d.channels);
                set('t-age', d.stale ? '⚠ snapshot stale' : '● live · updated just now');
                if (d.admin) {
                    set('t-banned', Number(d.banned).toLocaleString());
                    set('t-attacks', Number(d.attacks).toLocaleString());
                    set('g-cpu', d.cpu + '%'); set('g-mem', d.mem + '%');
                }
                var tb = document.querySelector('#live-table tbody');
                if (tb) {
                    if (!d.channel_list || !d.channel_list.length) {
                        tb.innerHTML = '<tr><td colspan="6" class="muted">No calls in progress.</td></tr>';
                    } else {
                        tb.innerHTML = d.channel_list.map(function(c){
                            var dir = c.direction === 'inbound' ? '<span class="pill dir-in">▼ inbound</span>'
                                    : (c.direction === 'outbound' ? '<span class="pill dir-out">▲ outbound</span>' : esc(c.direction));
                            return '<tr><td>'+esc(c.account_label||'—')+'</td><td class="muted">'+esc(c.trunk_label||'Endpoint')+'</td><td class="num">'+esc(c.cid)+'</td><td class="num">'+
                                   esc(c.dest)+'</td><td>'+dir+'</td><td class="muted">'+esc(c.codec)+'</td></tr>';
                        }).join('');
                    }
                    set('t-age2', Number(d.calls) + ' in progress');
                }
              })
              .catch(function(){ /* transient — next tick retries */ });
        }
        setInterval(tick, 10000);
    })();
    </script>
@endsection
