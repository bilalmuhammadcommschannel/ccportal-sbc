@extends('layouts.app')
@section('title','Endpoints')
@section('content')
    <div class="rowbar"><h1 style="margin:0">SIP endpoints</h1>@can('create',App\Models\Ov500\SipAccount::class)<a class="btn" href="{{ route('endpoints.create') }}">+ New endpoint</a>@endcan</div>
    <div class="card">
        <form method="GET" style="margin-bottom:16px;display:flex;gap:10px;max-width:420px"><input type="text" name="q" value="{{ $q }}" placeholder="Search username or name"><button class="btn ghost sm">Search</button></form>
        <table>
            <thead><tr><th>Registration</th><th>Username</th><th>Display</th><th>Account</th><th>Auth</th><th>Ch</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($endpoints as $e)
                @php $reg = $regs[strtolower($e->username)] ?? null; @endphp
                <tr>
                    <td>
                        @if($reg)
                            <span class="reg reg-on" tabindex="0"><span class="reg-led"></span>Registered
                                <span class="reg-pop">
                                    <span><b>IP</b>{{ $reg['ip'] ?: '—' }}</span>
                                    <span><b>User-Agent</b>{{ $reg['ua'] ?: '—' }}</span>
                                    <span><b>Expires</b>{{ $reg['expires'] ?: '—' }}</span>
                                </span>
                            </span>
                        @else
                            <span class="reg reg-off"><span class="reg-led"></span>Offline</span>
                        @endif
                    </td>
                    <td><a href="{{ route('endpoints.show',$e) }}">{{ $e->username }}</a></td>
                    <td>{{ $e->display_name }}</td>
                    <td class="muted">{{ $e->account_id }}</td>
                    <td>{{ $e->ipauthfrom==='NO' ? 'Password' : 'IP ('.$e->ipauthfrom.')' }}</td>
                    <td>{{ $e->sip_cc }}</td>
                    <td>@if((string)$e->status==='1')<span class="pill on">Active</span>@else<span class="pill off">Off</span>@endif</td>
                    <td class="right"><a class="btn ghost sm" href="{{ route('endpoints.edit',$e) }}">Edit</a></td>
                </tr>
            @empty<tr><td colspan="8" class="muted">No endpoints.</td></tr>@endforelse
            </tbody>
        </table>
        <style>
            .reg{position:relative;display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:600;cursor:default}
            .reg-led{width:10px;height:10px;border-radius:50%;flex:0 0 auto}
            .reg-on{color:#12855a}.reg-on .reg-led{background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.18)}
            .reg-off{color:#9aa7b4}.reg-off .reg-led{background:#c3ccd6}
            .reg-pop{display:none;position:absolute;z-index:30;left:0;top:150%;background:#0f1e30;color:#e9f1f9;
                     padding:10px 12px;border-radius:9px;box-shadow:0 8px 24px rgba(15,30,48,.28);font-weight:400;min-width:210px}
            .reg-pop span{display:block;white-space:nowrap;line-height:1.7}
            .reg-pop b{color:#8fb2d8;font-weight:600;display:inline-block;min-width:82px}
            .reg:hover .reg-pop,.reg:focus .reg-pop{display:block}
        </style>
        <div style="margin-top:14px">{{ $endpoints->links() }}</div>
    </div>
@endsection
