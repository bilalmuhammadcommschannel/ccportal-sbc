@extends('layouts.app')
@section('title', $tariff->tariff_name)
@section('content')
    <div class="rowbar"><h1 style="margin:0">{{ $tariff->tariff_name }}</h1>
        <div><a class="btn ghost sm" href="{{ route('tariffs.index') }}">← All</a> <a class="btn sm" href="{{ route('tariffs.edit',$tariff) }}">Edit</a></div>
    </div>
    <div class="card"><h2>Details</h2>
        <table>
            <tr><th style="width:200px">ID</th><td>{{ $tariff->tariff_id }}</td></tr>
            <tr><th>Type</th><td>{{ $tariff->tariff_type }}</td></tr>
            <tr><th>Status</th><td>@if($tariff->tariff_status==='1')<span class="pill on">Active</span>@else<span class="pill off">Inactive</span>@endif</td></tr>
            <tr><th>Monthly charge</th><td>{{ $tariff->monthly_charges }}</td></tr>
            <tr><th>Bundle</th><td>{{ $tariff->bundle_option==='1' ? 'Enabled' : 'Off' }}</td></tr>
        </table>
    </div>
    <div class="card"><h2>Ratecards</h2>
        <table>
            <thead><tr><th>Ratecard</th><th>Direction</th><th>Priority</th><th></th></tr></thead>
            <tbody>
            @forelse($tariff->maps as $m)
                <tr>
                    <td>@if($m->ratecard)<a href="{{ route('ratecards.show',$m->ratecard) }}">{{ $m->ratecard->ratecard_name }}</a>@else <span class="muted">{{ $m->ratecard_id }}</span>@endif</td>
                    <td>{{ $m->ratecard_for }}</td>
                    <td>{{ $m->priority }}</td>
                    <td class="right"><form method="POST" action="{{ route('tariffs.detach',[$tariff,$m]) }}" onsubmit="return confirm('Detach this ratecard?')">@csrf @method('DELETE')<button class="btn ghost sm">Detach</button></form></td>
                </tr>
            @empty<tr><td colspan="4" class="muted">No ratecards attached.</td></tr>@endforelse
            </tbody>
        </table>
        @if($attachable->isNotEmpty())
        <h2 style="margin-top:20px">Attach a ratecard</h2>
        <form method="POST" action="{{ route('tariffs.attach',$tariff) }}" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">@csrf
            <div class="field" style="margin:0"><label>Ratecard ({{ $tariff->tariff_type }})</label>
                <select name="ratecard_id" required><option value="">— select —</option>@foreach($attachable as $rc)<option value="{{ $rc->ratecard_id }}">{{ $rc->ratecard_name }} ({{ $rc->ratecard_for }})</option>@endforeach</select>
            </div>
            <div class="field" style="margin:0"><label>Direction</label><select name="ratecard_for"><option value="OUTGOING">OUTGOING</option><option value="INCOMING">INCOMING</option></select></div>
            <div class="field" style="margin:0"><label>Priority</label><input type="number" name="priority" value="1" min="1" style="width:90px"></div>
            <button class="btn" type="submit">Attach</button>
        </form>
        @else<p class="muted">No more {{ $tariff->tariff_type }} ratecards to attach — <a href="{{ route('ratecards.create') }}">create one</a>.</p>@endif
    </div>
@endsection
