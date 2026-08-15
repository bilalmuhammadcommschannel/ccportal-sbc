@extends('layouts.app')
@section('title','DID '.$did->did_number)
@section('content')
    <div class="rowbar"><h1 style="margin:0">{{ $did->did_number }}</h1>
        <div><a class="btn ghost sm" href="{{ route('dids.index') }}">← All</a> <a class="btn sm" href="{{ route('dids.edit',$did) }}">Assign / edit</a></div>
    </div>
    <div class="card"><h2>DID</h2>
        <table>
            <tr><th style="width:200px">Status</th><td>{{ $did->did_status }}</td></tr>
            <tr><th>Type</th><td>{{ $did->number_type }}</td></tr>
            <tr><th>Channels</th><td>{{ $did->channels }}</td></tr>
            <tr><th>Assigned account</th><td>{{ $did->account_id ?? '— unassigned —' }}</td></tr>
            <tr><th>Reseller chain</th><td>{{ collect([$did->reseller1_account_id,$did->reseller2_account_id,$did->reseller3_account_id])->filter()->implode(' → ') ?: '—' }}</td></tr>
            <tr><th>Assigned date</th><td class="muted">{{ $did->assign_date ?? '—' }}</td></tr>
        </table>
    </div>
    <div class="card"><h2>Routing destination</h2>
        @if($did->destination)
            <table>
                <tr><th style="width:200px">Primary</th><td>{{ $did->destination->dst_type }} → {{ $did->destination->dst_destination }}</td></tr>
                @if($did->destination->dst_destination2)<tr><th>Failover</th><td>{{ $did->destination->dst_type2 }} → {{ $did->destination->dst_destination2 }}</td></tr>@endif
            </table>
        @else<p class="muted">No routing destination set.</p>@endif
    </div>
    @can('release',$did)
    <form method="POST" action="{{ route('dids.release',$did) }}" onsubmit="return confirm('Release {{ $did->did_number }} back to inventory?')">@csrf <button class="btn ghost">Release to inventory</button></form>
    @endcan
@endsection
