@extends('layouts.app')
@section('title', optional($customer->customerRow)->company_name ?? $customer->account_id)
@section('content')
    <div class="rowbar"><h1 style="margin:0">{{ optional($customer->customerRow)->company_name ?? $customer->account_id }}</h1>
        <div>
            <a class="btn ghost sm" href="{{ route('customers.index') }}">← All</a>
            <a class="btn sm" href="{{ route('customers.edit',$customer) }}">Edit</a>
            @can('delete',$customer)
            <form method="POST" action="{{ route('customers.destroy',$customer) }}" style="display:inline"
                  onsubmit="return confirm('Delete this customer?\n\nIf it has any call history, balance, endpoints, DIDs or sub-accounts it is ARCHIVED (status Closed) and its service is disabled — all records are kept. Only a customer with no history at all is permanently removed.');">
                @csrf @method('DELETE')
                <button type="submit" class="btn ghost sm" style="color:#b91c1c;border-color:#b91c1c">Delete / Archive</button>
            </form>
            @endcan
        </div>
    </div>
    <div class="card"><h2>Customer</h2>
        <table>
            <tr><th style="width:200px">Account ID</th><td>{{ $customer->account_id }}</td></tr>
            <tr><th>Parent</th><td>{{ $customer->parent_account_id }}</td></tr>
            <tr><th>Contact</th><td>{{ optional($customer->customerRow)->contact_name }} · {{ optional($customer->customerRow)->emailaddress }}</td></tr>
            <tr><th>Billing</th><td>{{ optional($customer->customerRow)->billing_type }} / {{ optional($customer->customerRow)->billing_cycle }}</td></tr>
            <tr><th>Tariff</th><td>{{ optional($tariff)->tariff_id ?? '— none —' }}</td></tr>
            <tr><th>Balance</th><td><a href="{{ route('balances.show',$customer) }}">{{ number_format((float) optional($customer->balance)->balance, 4) }}</a></td></tr>
            <tr><th>Endpoints / DIDs</th><td>{{ $endpoints }} / {{ $dids }}</td></tr>
            <tr><th>Status</th><td>@if((string)$customer->status_id==='1')<span class="pill on">Active</span>@else<span class="pill off">{{ $customer->status_id }}</span>@endif</td></tr>
        </table>
    </div>
@endsection
