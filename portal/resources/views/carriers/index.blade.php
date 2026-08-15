@extends('layouts.app')
@section('title', 'Carriers')
@section('content')
    <div class="rowbar">
        <h1 style="margin:0">Carriers</h1>
        <a class="btn" href="{{ route('carriers.create') }}">+ New carrier</a>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('carriers.index') }}" style="margin-bottom:16px;display:flex;gap:10px;max-width:420px">
            <input type="text" name="q" value="{{ $q }}" placeholder="Search name or ID">
            <button class="btn ghost sm" type="submit">Search</button>
        </form>

        <table>
            <thead>
                <tr><th>Name</th><th>ID</th><th>Type</th><th>Endpoints</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($carriers as $c)
                    <tr>
                        <td><a href="{{ route('carriers.show', $c) }}">{{ $c->carrier_name }}</a></td>
                        <td class="muted">{{ $c->carrier_id }}</td>
                        <td>{{ $c->carrier_type }}</td>
                        <td>{{ $c->ips_count }}</td>
                        <td>
                            @if($c->carrier_status === 1)
                                <span class="pill on">Active</span>
                            @else
                                <span class="pill off">Inactive</span>
                            @endif
                        </td>
                        <td class="right"><a class="btn ghost sm" href="{{ route('carriers.edit', $c) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No carriers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $carriers->links() }}</div>
    </div>
@endsection
