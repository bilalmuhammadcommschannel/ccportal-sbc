@extends('layouts.app')
@section('title', $ratecard->ratecard_name)
@section('content')
    <div class="rowbar">
        <h1 style="margin:0">{{ $ratecard->ratecard_name }} <span class="muted" style="font-size:14px">{{ $ratecard->ratecard_type }} · {{ $ratecard->ratecard_for }}</span></h1>
        <div><a class="btn ghost sm" href="{{ route('ratecards.index') }}">← All</a> <a class="btn sm" href="{{ route('ratecards.bulk',$ratecard) }}">Bulk import</a></div>
    </div>

    @if(session('import_fails'))
        <div class="errs"><strong>Skipped rows:</strong><ul>@foreach(session('import_fails') as $f)<li>{{ $f }}</li>@endforeach</ul></div>
    @endif

    <div class="card">
        <h2>Add / update a rate</h2>
        <p class="muted" style="margin-top:-6px">Pulse = first/next seconds (e.g. 60/60, 6/6, 1/1). Per-channel = inclusive channels + per-extra-channel rental.</p>
        <form method="POST" action="{{ route('rates.store',$ratecard) }}">@csrf
            <div class="grid3">
                <div class="field"><label>Prefix</label><input name="prefix" placeholder="e.g. 6141" required></div>
                <div class="field"><label>Destination</label><input name="destination" placeholder="e.g. Australia Mobile" required></div>
                <div class="field"><label>Rate (per min)</label><input name="rate" value="0" required></div>
            </div>
            <div class="grid3">
                <div class="field"><label>First pulse (s)</label><input type="number" name="minimal_time" value="60"></div>
                <div class="field"><label>Next pulse (s)</label><input type="number" name="resolution_time" value="60"></div>
                <div class="field"><label>Grace period (s)</label><input type="number" name="grace_period" value="0"></div>
            </div>
            <div class="grid3">
                <div class="field"><label>Setup charge</label><input name="setup_charge" value="0"></div>
                <div class="field"><label>Connection charge</label><input name="connection_charge" value="0"></div>
                <div class="field"><label>Minimal charge</label><input name="minimal_charge" value=""></div>
            </div>
            <div class="grid3">
                <div class="field"><label>Inclusive channels</label><input type="number" name="inclusive_channel" value="1"></div>
                <div class="field"><label>Per-channel rental</label><input name="exclusive_per_channel_rental" value="0"></div>
                <div class="field"><label>Status</label><select name="rates_status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            </div>
            <button class="btn" type="submit">Save rate</button>
        </form>
    </div>

    <div class="card">
        <div class="rowbar"><h2 style="margin:0">Rates ({{ $rates->total() }})</h2>
            <form method="GET" style="display:flex;gap:8px"><input type="text" name="q" value="{{ $q }}" placeholder="prefix or destination"><button class="btn ghost sm">Search</button></form>
        </div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Prefix</th><th>Destination</th><th class="right">Rate</th><th>Pulse</th><th class="right">Setup</th><th class="right">Conn</th><th>Incl ch</th><th class="right">Per-ch</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($rates as $r)
                <tr>
                    <td>{{ $r->prefix }}</td><td>{{ $r->destination }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float)$r->rate,6),'0'),'.') }}</td>
                    <td>{{ $r->minimal_time }}/{{ $r->resolution_time }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float)$r->setup_charge,6),'0'),'.') }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float)$r->connection_charge,6),'0'),'.') }}</td>
                    <td>{{ $r->inclusive_channel }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float)$r->exclusive_per_channel_rental,6),'0'),'.') }}</td>
                    <td>@if((string)$r->rates_status==='1')<span class="pill on">On</span>@else<span class="pill off">Off</span>@endif</td>
                    <td class="right"><form method="POST" action="{{ route('rates.destroy',[$ratecard,$r->prefix]) }}" onsubmit="return confirm('Delete rate {{ $r->prefix }}?')">@csrf @method('DELETE')<button class="btn ghost sm">✕</button></form></td>
                </tr>
            @empty<tr><td colspan="10" class="muted">No rates yet. Add one above or bulk import.</td></tr>@endforelse
            </tbody>
        </table>
        </div>
        <div style="margin-top:14px">{{ $rates->links() }}</div>
    </div>
@endsection
