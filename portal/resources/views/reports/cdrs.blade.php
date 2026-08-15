@extends('layouts.app')
@section('title','CDRs')
@section('content')
    <h1>Call records (CDRs)</h1>

    <div class="card">
        <form method="GET" action="{{ route('cdrs.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
            <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="{{ $from }}"></div>
            <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="{{ $to }}"></div>
            <div class="field" style="margin:0"><label>Account</label><input name="account" value="{{ $acct }}" placeholder="STC300000"></div>
            <div class="field" style="margin:0"><label>Direction</label><select name="direction"><option value="">Any</option><option value="outbound" @selected($dir==='outbound')>Outbound</option><option value="inbound" @selected($dir==='inbound')>Inbound</option></select></div>
            <div class="field" style="margin:0"><label>Destination</label><input name="dest" value="{{ $dest }}" placeholder="614…"></div>
            <button class="btn ghost sm" type="submit">Filter</button>
        </form>
    </div>

    <div class="card" style="display:flex;gap:40px">
        <div><div class="muted">Calls</div><div style="font-size:24px;font-weight:700">{{ number_format($summary['count']) }}</div></div>
        <div><div class="muted">Billed minutes</div><div style="font-size:24px;font-weight:700">{{ number_format($summary['seconds']/60, 1) }}</div></div>
        <div><div class="muted">Total cost</div><div style="font-size:24px;font-weight:700;color:var(--blue)">{{ number_format((float)$summary['cost'], 4) }}</div></div>
    </div>

    <div class="card">
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Rated at</th><th>Account</th><th>Dir</th><th>Destination</th><th>Carrier</th><th class="right">Billsec</th><th class="right">Billed</th><th class="right">Rate</th><th class="right">Cost</th></tr></thead>
            <tbody>
            @forelse($cdrs as $c)
                <tr>
                    <td class="muted">{{ $c->rated_at }}</td>
                    <td>{{ $c->account_id }}</td>
                    <td>{{ $c->direction }}</td>
                    <td>{{ $c->destination_number }}</td>
                    <td class="muted">{{ $c->carrier_id }}</td>
                    <td class="right">{{ $c->billsec }}</td>
                    <td class="right">{{ $c->billed_seconds }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float)$c->rate,6),'0'),'.') }}</td>
                    <td class="right">{{ number_format((float)$c->cost,4) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">No CDRs yet. Rated calls appear here once traffic flows through the switch.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <div style="margin-top:14px">{{ $cdrs->links() }}</div>
    </div>
@endsection
