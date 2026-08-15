@extends('layouts.app')
@section('title','Customers')
@section('content')
    <div class="rowbar"><h1 style="margin:0">Customers</h1>@can('create',App\Models\Ov500\Account::class)<a class="btn" href="{{ route('customers.create') }}">+ New customer</a>@endcan</div>
    <div class="card">
        <form method="GET" style="margin-bottom:16px;display:flex;gap:10px;max-width:420px"><input type="text" name="q" value="{{ $q }}" placeholder="Search account ID"><button class="btn ghost sm">Search</button></form>
        <table>
            <thead><tr><th>Company</th><th>Account</th><th>Billing</th><th class="right">Balance</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($customers as $c)
                <tr>
                    <td><a href="{{ route('customers.show',$c) }}">{{ optional($c->customerRow)->company_name ?? '—' }}</a></td>
                    <td class="muted">{{ $c->account_id }}</td>
                    <td>{{ optional($c->customerRow)->billing_type ?? '—' }}</td>
                    <td class="right">{{ number_format((float) optional($c->balance)->balance, 2) }}</td>
                    <td>@if((string)$c->status_id==='1')<span class="pill on">Active</span>@else<span class="pill off">{{ $c->status_id }}</span>@endif</td>
                    <td class="right"><a class="btn ghost sm" href="{{ route('customers.edit',$c) }}">Edit</a></td>
                </tr>
            @empty<tr><td colspan="6" class="muted">No customers.</td></tr>@endforelse
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $customers->links() }}</div>
    </div>
@endsection
