@extends('layouts.app')
@section('title','Endpoints')
@section('content')
    <div class="rowbar"><h1 style="margin:0">SIP endpoints</h1>@can('create',App\Models\Ov500\SipAccount::class)<a class="btn" href="{{ route('endpoints.create') }}">+ New endpoint</a>@endcan</div>
    <div class="card">
        <form method="GET" style="margin-bottom:16px;display:flex;gap:10px;max-width:420px"><input type="text" name="q" value="{{ $q }}" placeholder="Search username or name"><button class="btn ghost sm">Search</button></form>
        <table>
            <thead><tr><th>Username</th><th>Display</th><th>Account</th><th>Auth</th><th>Ch</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($endpoints as $e)
                <tr>
                    <td><a href="{{ route('endpoints.show',$e) }}">{{ $e->username }}</a></td>
                    <td>{{ $e->display_name }}</td>
                    <td class="muted">{{ $e->account_id }}</td>
                    <td>{{ $e->ipauthfrom==='NO' ? 'Password' : 'IP ('.$e->ipauthfrom.')' }}</td>
                    <td>{{ $e->sip_cc }}</td>
                    <td>@if((string)$e->status==='1')<span class="pill on">Active</span>@else<span class="pill off">Off</span>@endif</td>
                    <td class="right"><a class="btn ghost sm" href="{{ route('endpoints.edit',$e) }}">Edit</a></td>
                </tr>
            @empty<tr><td colspan="7" class="muted">No endpoints.</td></tr>@endforelse
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $endpoints->links() }}</div>
    </div>
@endsection
