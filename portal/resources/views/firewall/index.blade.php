@extends('layouts.app')
@section('title', 'Firewall')
@section('topbar-right')
    <span class="muted" style="font-size:12px;font-weight:500">{{ $stale ? '⚠ snapshot stale' : '● '.number_format($totalBanned).' blocked · refreshed '.($age ?? '?').'s ago' }}</span>
@endsection
@section('content')
@php
    $jailColor = [
        'ccsip-scanner' => '#2E7BC4', 'ccsip-auth' => '#59a6e0',
        'ccportal-probe' => '#b57500', 'ccportal-auth' => '#7c5cc4', 'sshd' => '#64748b',
    ];
@endphp

    @if($stale)
        <div class="errs">Ban list is stale ({{ $age ?? '?' }}s old) — it comes from <code>cc-stats.timer</code>. Unblock still works.</div>
    @endif

    <div class="card">
        <div class="rowbar">
            <h2 style="margin:0">Blocked IP addresses</h2>
            <span class="muted" style="font-size:12px">{{ number_format($totalBanned) }} currently blocked · {{ number_format($totalFailed) }} attempts stopped</span>
        </div>

        <div class="searchbar">
            <input type="search" id="fw-search" placeholder="Search IP address…" autocomplete="off">
            <div class="chips" id="fw-chips">
                <button class="chip-btn active" data-jail="">All <b class="num">{{ number_format($totalBanned) }}</b></button>
                @foreach($jailCounts as $name => $cnt)
                    @if($cnt > 0)
                        <button class="chip-btn" data-jail="{{ $name }}"><span class="sw" style="display:inline-block;width:8px;height:8px;border-radius:2px;background:{{ $jailColor[$name] ?? '#9db2c6' }};margin-right:5px"></span>{{ $name }} <b class="num">{{ number_format($cnt) }}</b></button>
                    @endif
                @endforeach
            </div>
        </div>

        <div style="overflow-x:auto">
            <table id="fw-table">
                <thead><tr><th>IP address</th><th>Jail</th><th>Blocked at</th><th>Expires</th><th class="right">Action</th></tr></thead>
                <tbody>
                @forelse($bans as $b)
                    <tr data-ip="{{ $b['ip'] }}" data-jail="{{ $b['jail'] }}">
                        <td class="mono num">{{ $b['ip'] }}</td>
                        <td><span class="pill" style="background:{{ ($jailColor[$b['jail']] ?? '#9db2c6') }}1f;color:{{ $jailColor[$b['jail']] ?? '#5b6b7c' }}">{{ $b['jail'] }}</span></td>
                        <td class="muted num" style="font-size:13px">{{ $b['since'] ?: '—' }}</td>
                        <td class="muted num" style="font-size:13px">{{ $b['until'] ?: '—' }}</td>
                        <td class="right">
                            <form method="POST" action="{{ route('firewall.unban') }}" onsubmit="return confirm('Unblock {{ $b['ip'] }} from {{ $b['jail'] }}?');" style="display:inline">
                                @csrf
                                <input type="hidden" name="ip" value="{{ $b['ip'] }}">
                                <input type="hidden" name="jail" value="{{ $b['jail'] }}">
                                <button class="btn ghost sm">Unblock</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr id="fw-empty"><td colspan="5" class="muted">Nothing is currently blocked.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="muted" id="fw-count" style="font-size:12px;margin:12px 2px 0"></p>
    </div>

    <script>
    (function () {
        var search = document.getElementById('fw-search');
        var rows = Array.prototype.slice.call(document.querySelectorAll('#fw-table tbody tr[data-ip]'));
        var chips = document.getElementById('fw-chips');
        var counter = document.getElementById('fw-count');
        var jail = '';
        function apply() {
            var q = (search.value || '').trim().toLowerCase();
            var shown = 0;
            rows.forEach(function (r) {
                var okIp = !q || r.getAttribute('data-ip').toLowerCase().indexOf(q) !== -1;
                var okJail = !jail || r.getAttribute('data-jail') === jail;
                var show = okIp && okJail;
                r.style.display = show ? '' : 'none';
                if (show) shown++;
            });
            counter.textContent = shown + ' of ' + rows.length + ' shown';
        }
        if (search) search.addEventListener('input', apply);
        if (chips) chips.addEventListener('click', function (e) {
            var btn = e.target.closest('.chip-btn'); if (!btn) return;
            jail = btn.getAttribute('data-jail') || '';
            chips.querySelectorAll('.chip-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            apply();
        });
        apply();
    })();
    </script>
@endsection
