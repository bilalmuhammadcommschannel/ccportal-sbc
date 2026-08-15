@extends('layouts.app')
@section('title', $carrier->carrier_name)
@section('content')
    <div class="rowbar">
        <h1 style="margin:0">{{ $carrier->carrier_name }}</h1>
        <div>
            <a class="btn ghost sm" href="{{ route('carriers.index') }}">← All carriers</a>
            <a class="btn sm" href="{{ route('carriers.edit', $carrier) }}">Edit</a>
        </div>
    </div>

    <div class="card">
        <h2>Details</h2>
        <table>
            <tr><th style="width:200px">Carrier ID</th><td>{{ $carrier->carrier_id }}</td></tr>
            <tr><th>Direction</th><td>{{ $carrier->carrier_type }}</td></tr>
            <tr><th>Status</th><td>@if($carrier->carrier_status === 1)<span class="pill on">Active</span>@else<span class="pill off">Inactive</span>@endif</td></tr>
            <tr><th>Tariff</th><td>{{ $carrier->tariff_id }}</td></tr>
            <tr><th>Max CPS / channels</th><td>{{ $carrier->carrier_cps }} / {{ $carrier->carrier_cc }}</td></tr>
            <tr><th>CLI prefer</th><td>{{ $carrier->cli_prefer }}</td></tr>
            <tr><th>Codecs</th><td>{{ $carrier->carrier_codecs }}</td></tr>
            <tr><th>Tax type</th><td>{{ $carrier->tax_type }}</td></tr>
            <tr><th>Created</th><td class="muted">{{ $carrier->created_by }} · {{ $carrier->created_dt }}</td></tr>
            <tr><th>Updated</th><td class="muted">{{ $carrier->updated_by }} · {{ $carrier->updated_dt }}</td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Signalling endpoints ({{ $carrier->ips->count() }})</h2>
        <table>
            <thead><tr><th>Name</th><th>IP</th><th>Auth</th><th>Credentials</th><th>Weight/Prio</th></tr></thead>
            <tbody>
                @forelse($carrier->ips as $ip)
                    <tr>
                        <td>{{ $ip->ipaddress_name }}</td>
                        <td>{{ $ip->ipaddress }}</td>
                        <td>{{ $ip->auth_type }}</td>
                        <td>
                            @if($ip->auth_type === 'CUSTOMER')
                                {{ $ip->username }} / <span class="muted">••••••</span>
                            @else
                                <span class="muted">IP-authenticated</span>
                            @endif
                        </td>
                        <td>{{ $ip->load_share }} / {{ $ip->priority }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No endpoints.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
