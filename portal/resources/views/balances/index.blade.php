@extends('layouts.app')
@section('title', 'Balances')
@section('content')
    <h1>Customer balances</h1>
    <div class="card">
        <form method="GET" action="{{ route('balances.index') }}" style="margin-bottom:16px;display:flex;gap:10px;max-width:420px">
            <input type="text" name="q" value="{{ $q }}" placeholder="Search account ID">
            <button class="btn ghost sm" type="submit">Search</button>
        </form>
        <table>
            <thead><tr><th>Account</th><th>Type</th><th class="right">Balance</th><th class="right">Credit limit</th><th></th></tr></thead>
            <tbody>
                @forelse($accounts as $a)
                    <tr>
                        <td><a href="{{ route('balances.show', $a) }}">{{ $a->display_name }}</a></td>
                        <td>{{ $a->account_type }}</td>
                        <td class="right">{{ number_format((float) optional($a->balance)->balance, 4) }}</td>
                        <td class="right muted">{{ number_format((float) optional($a->balance)->credit_limit, 4) }}</td>
                        <td class="right"><a class="btn ghost sm" href="{{ route('balances.show', $a) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No accounts in scope.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $accounts->links() }}</div>
    </div>
@endsection
