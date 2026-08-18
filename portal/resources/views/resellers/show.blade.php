@extends('layouts.app')
@section('title', optional($reseller->resellerRow)->company_name ?? $reseller->account_id)
@section('content')
    <div class="rowbar"><h1 style="margin:0">{{ optional($reseller->resellerRow)->company_name ?? $reseller->account_id }}</h1>
        <div><a class="btn ghost sm" href="{{ route('resellers.index') }}">← All</a> <a class="btn sm" href="{{ route('resellers.edit',$reseller) }}">Edit</a></div>
    </div>
    <div class="card"><h2>Reseller</h2>
        <table>
            <tr><th style="width:200px">Account ID</th><td>{{ $reseller->account_id }}</td></tr>
            <tr><th>Parent</th><td>{{ $reseller->parent_account_id }}</td></tr>
            <tr><th>Contact</th><td>{{ optional($reseller->resellerRow)->contact_name }} · {{ optional($reseller->resellerRow)->emailaddress }}</td></tr>
            <tr><th>Balance</th><td><a href="{{ route('balances.show',$reseller) }}">{{ number_format((float) optional($reseller->balance)->balance, 4) }}</a></td></tr>
            <tr><th>Sub-accounts</th><td>{{ $subCustomers }} customer(s), {{ $subResellers }} reseller(s)</td></tr>
            <tr><th>Status</th><td><span class="pill {{ $reseller->status_pill }}">{{ $reseller->status_label }}</span></td></tr>
        </table>
    </div>
@endsection
