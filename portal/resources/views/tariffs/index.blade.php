@extends('layouts.app')
@section('title', 'Tariffs')
@section('content')
    <div class="rowbar"><h1 style="margin:0">Tariffs</h1><a class="btn" href="{{ route('tariffs.create') }}">+ New tariff</a></div>
    <div class="card">
        <form method="GET" style="margin-bottom:16px;display:flex;gap:10px;max-width:420px">
            <input type="text" name="q" value="{{ $q }}" placeholder="Search name or ID"><button class="btn ghost sm">Search</button>
        </form>
        <table>
            <thead><tr><th>Name</th><th>ID</th><th>Type</th><th>Bundle</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($tariffs as $t)
                <tr>
                    <td><a href="{{ route('tariffs.show',$t) }}">{{ $t->tariff_name }}</a></td>
                    <td class="muted">{{ $t->tariff_id }}</td>
                    <td>{{ $t->tariff_type }}</td>
                    <td>{{ $t->bundle_option === '1' ? 'Yes' : '—' }}</td>
                    <td>@if($t->tariff_status === '1')<span class="pill on">Active</span>@else<span class="pill off">Inactive</span>@endif</td>
                    <td class="right"><a class="btn ghost sm" href="{{ route('tariffs.edit',$t) }}">Edit</a></td>
                </tr>
            @empty<tr><td colspan="6" class="muted">No tariffs.</td></tr>@endforelse
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $tariffs->links() }}</div>
    </div>
@endsection
