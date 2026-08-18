@extends('layouts.app')
@section('title','Resellers')
@section('content')
    <div class="rowbar"><h1 style="margin:0">Resellers</h1>@can('create',App\Models\Ov500\Account::class)<a class="btn" href="{{ route('resellers.create') }}">+ New reseller</a>@endcan</div>
    <div class="card">
        <form method="GET" style="margin-bottom:16px;display:flex;gap:10px;max-width:420px"><input type="text" name="q" value="{{ $q }}" placeholder="Search account ID"><button class="btn ghost sm">Search</button></form>
        <table>
            <thead><tr><th>Company</th><th>Account</th><th>Parent</th><th class="right">Balance</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($resellers as $r)
                <tr>
                    <td><a href="{{ route('resellers.show',$r) }}">{{ optional($r->resellerRow)->company_name ?? '—' }}</a></td>
                    <td class="muted">{{ $r->account_id }}</td>
                    <td class="muted">{{ $r->parent_account_id }}</td>
                    <td class="right">{{ number_format((float) optional($r->balance)->balance, 2) }}</td>
                    <td><span class="pill {{ $r->status_pill }}">{{ $r->status_label }}</span></td>
                    <td class="right"><a class="btn ghost sm" href="{{ route('resellers.edit',$r) }}">Edit</a></td>
                </tr>
            @empty<tr><td colspan="6" class="muted">No resellers.</td></tr>@endforelse
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $resellers->links() }}</div>
    </div>
@endsection
