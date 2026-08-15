@extends('layouts.app')
@section('title', 'Ratecards')
@section('content')
    <div class="rowbar"><h1 style="margin:0">Ratecards</h1><a class="btn" href="{{ route('ratecards.create') }}">+ New ratecard</a></div>
    <div class="card">
        <form method="GET" style="margin-bottom:16px;display:flex;gap:10px;max-width:420px"><input type="text" name="q" value="{{ $q }}" placeholder="Search name or ID"><button class="btn ghost sm">Search</button></form>
        <table>
            <thead><tr><th>Name</th><th>ID</th><th>Type</th><th>Direction</th><th></th></tr></thead>
            <tbody>
            @forelse($ratecards as $rc)
                <tr><td><a href="{{ route('ratecards.show',$rc) }}">{{ $rc->ratecard_name }}</a></td><td class="muted">{{ $rc->ratecard_id }}</td><td>{{ $rc->ratecard_type }}</td><td>{{ $rc->ratecard_for }}</td><td class="right"><a class="btn ghost sm" href="{{ route('ratecards.show',$rc) }}">Rates</a></td></tr>
            @empty<tr><td colspan="5" class="muted">No ratecards.</td></tr>@endforelse
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $ratecards->links() }}</div>
    </div>
@endsection
