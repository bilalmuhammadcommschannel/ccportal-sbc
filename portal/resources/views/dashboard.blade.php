@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

    @if(($stats['stale'] ?? true))
        <div class="errs">Monitoring snapshot is stale ({{ $stats['age_sec'] ?? '?' }}s old) — check <code>cc-stats.timer</code>.</div>
    @endif

    {{-- live call + today's traffic tiles --}}
    <div class="tiles">
        <div class="tile hero">
            <div class="tl">Live calls</div>
            <div class="tv" id="t-calls">{{ data_get($stats,'freeswitch.calls',0) }}</div>
            <div class="ts"><span id="t-chans">{{ data_get($stats,'freeswitch.channels',0) }}</span> channels
                @if(!data_get($stats,'freeswitch.up'))<span class="pill off">switch down</span>@endif
            </div>
        </div>
        <div class="tile">
            <div class="tl">Calls today</div>
            <div class="tv">{{ number_format($traffic['calls']) }}</div>
            <div class="ts">{{ $traffic['minutes'] }} billed min</div>
        </div>
        <div class="tile">
            <div class="tl">Charged today</div>
            <div class="tv">{{ number_format((float) $traffic['cost'], 2) }}</div>
            <div class="ts"><a href="{{ route('cdrs.index') }}">view CDRs →</a></div>
        </div>
        @if($isAdmin)
        <div class="tile warn">
            <div class="tl">Blocked IPs</div>
            <div class="tv" id="t-banned">{{ data_get($stats,'fail2ban.currently_banned',0) }}</div>
            <div class="ts"><span id="t-attacks">{{ number_format(data_get($stats,'fail2ban.total_failed',0)) }}</span> attempts blocked</div>
        </div>
        @endif
    </div>

    {{-- live calls --}}
    <div class="card">
        <div class="rowbar"><h2 style="margin:0">Live calls</h2><span class="muted" id="t-age">updated {{ $stats['age_sec'] ?? '?' }}s ago</span></div>
        <div style="overflow-x:auto">
            <table id="live-table">
                <thead><tr><th>Channel</th><th>Dir</th><th>Caller</th><th>Destination</th><th>State</th><th>Codec</th></tr></thead>
                <tbody>
                @forelse(data_get($stats,'freeswitch.channel_list',[]) as $c)
                    <tr>
                        <td class="muted" style="font-size:12px">{{ \Illuminate\Support\Str::limit($c['name'] ?? '', 34) }}</td>
                        <td>{{ $c['direction'] ?? '' }}</td><td>{{ $c['cid'] ?? '' }}</td>
                        <td>{{ $c['dest'] ?? '' }}</td><td>{{ $c['state'] ?? '' }}</td><td class="muted">{{ $c['codec'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No calls in progress.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="cols">
        {{-- prepaid accounts running low --}}
        <div class="card">
            <h2>Prepaid balances running low</h2>
            <table>
                <thead><tr><th>Account</th><th class="right">Balance</th><th></th></tr></thead>
                <tbody>
                @forelse($lowBalance as $b)
                    <tr>
                        <td>{{ $b->company_name ?? $b->account_id }}<br><span class="muted" style="font-size:12px">{{ $b->account_id }}</span></td>
                        <td class="right" style="color:{{ (float)$b->balance <= 0 ? '#d93025' : 'inherit' }}">{{ number_format((float)$b->balance, 4) }}</td>
                        <td class="right"><a class="btn ghost sm" href="{{ route('balances.show', $b->account_id) }}">Top up</a></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">No prepaid accounts below threshold.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- platform inventory --}}
        <div class="card">
            <h2>Platform</h2>
            <table>
                <tr><th>Customers</th><td>{{ number_format($counts['customers']) }}</td></tr>
                <tr><th>Resellers</th><td>{{ number_format($counts['resellers']) }}</td></tr>
                <tr><th>SIP endpoints</th><td>{{ number_format($counts['endpoints']) }}</td></tr>
                <tr><th>DIDs</th><td>{{ number_format($counts['dids']) }}</td></tr>
                @if($isAdmin)<tr><th>Carriers</th><td>{{ number_format($counts['carriers']) }}</td></tr>@endif
            </table>
        </div>
    </div>

    @if($isAdmin)
    <div class="cols">
        {{-- fail2ban / security --}}
        <div class="card">
            <h2>Security — fail2ban</h2>
            <table>
                <thead><tr><th>Jail</th><th class="right">Attempts</th><th class="right">Banned now</th><th class="right">Total bans</th></tr></thead>
                <tbody>
                @forelse(data_get($stats,'fail2ban.jails',[]) as $j)
                    <tr>
                        <td>{{ $j['name'] }}</td>
                        <td class="right">{{ number_format($j['total_failed']) }}</td>
                        <td class="right">@if($j['currently_banned']>0)<span class="pill warn">{{ $j['currently_banned'] }}</span>@else 0 @endif</td>
                        <td class="right muted">{{ number_format($j['total_banned']) }}</td>
                    </tr>
                    @if(!empty(trim($j['banned_ips'])))
                    <tr><td colspan="4" class="muted" style="font-size:12px;padding-top:0">blocked: {{ $j['banned_ips'] }}</td></tr>
                    @endif
                @empty
                    <tr><td colspan="4" class="muted">No jail data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- services + host --}}
        <div class="card">
            <h2>Services</h2>
            <div class="svc">
                @foreach(data_get($stats,'services',[]) as $s)
                    <span class="pill {{ $s['state']==='active' ? 'on' : 'off' }}">{{ str_replace(['.timer','.service'],'',$s['name']) }}</span>
                @endforeach
            </div>
            <table style="margin-top:14px">
                <tr><th style="width:150px">Load</th><td>{{ data_get($stats,'host.load','?') }}</td></tr>
                <tr><th>Memory</th><td>{{ data_get($stats,'host.mem_available_mb',0) }} MB free of {{ data_get($stats,'host.mem_total_mb',0) }} MB</td></tr>
                <tr><th>Disk</th><td>{{ data_get($stats,'host.disk_used_pct',0) }}% used</td></tr>
                <tr><th>Uptime</th><td>{{ round(data_get($stats,'host.uptime_sec',0)/3600, 1) }} h</td></tr>
            </table>
        </div>
    </div>
    @endif

    <style>
        .tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:18px}
        .tile{background:#fff;border:1px solid var(--line);border-radius:8px;padding:16px}
        .tile.hero{background:var(--rail);border-color:var(--rail)}
        .tile.hero .tl,.tile.hero .ts{color:var(--dim)}.tile.hero .tv{color:#fff}
        .tile.warn{border-color:#f0c36d}
        .tl{font-size:12px;color:#5f6368;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
        .tv{font-size:32px;font-weight:700;color:var(--blue);line-height:1.1;margin:6px 0 2px}
        .tile.warn .tv{color:#b06000}
        .ts{font-size:12px;color:#5f6368}
        .cols{display:grid;grid-template-columns:1fr 1fr;gap:18px}
        @media (max-width:900px){.cols{grid-template-columns:1fr}}
        .pill.warn{background:#fef7e0;color:#b06000}
        .svc{display:flex;flex-wrap:wrap;gap:6px}
    </style>

    <script>
    // Poll the live endpoint so calls/bans update without a page reload.
    (function () {
        var url = @json(route('dashboard.live'));
        function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
        function tick() {
            fetch(url, {headers:{'Accept':'application/json'}, credentials:'same-origin'})
              .then(function(r){ return r.ok ? r.json() : null; })
              .then(function(d){
                if (!d) return;
                var set = function(id,v){ var e=document.getElementById(id); if(e) e.textContent=v; };
                set('t-calls', d.calls); set('t-chans', d.channels);
                if (d.admin) { set('t-banned', d.banned); set('t-attacks', Number(d.attacks).toLocaleString()); }
                set('t-age', 'updated just now');
                var tb = document.querySelector('#live-table tbody');
                if (tb) {
                    if (!d.channel_list || !d.channel_list.length) {
                        tb.innerHTML = '<tr><td colspan="6" class="muted">No calls in progress.</td></tr>';
                    } else {
                        tb.innerHTML = d.channel_list.map(function(c){
                            return '<tr><td class="muted" style="font-size:12px">'+esc((c.name||'').slice(0,34))+'</td><td>'+esc(c.direction)+'</td><td>'+esc(c.cid)+'</td><td>'+
                                   esc(c.dest)+'</td><td>'+esc(c.state)+'</td><td class="muted">'+esc(c.codec)+'</td></tr>';
                        }).join('');
                    }
                }
              })
              .catch(function(){ /* transient — next tick retries */ });
        }
        setInterval(tick, 10000);
    })();
    </script>
@endsection
