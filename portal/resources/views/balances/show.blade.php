@extends('layouts.app')
@section('title', 'Balance · '.$account->account_id)
@section('content')
    <div class="rowbar">
        <h1 style="margin:0">{{ $account->display_name }}</h1>
        <a class="btn ghost sm" href="{{ route('balances.index') }}">← All balances</a>
    </div>

    <div class="card">
        <h2>Current balance</h2>
        <div style="font-size:30px;font-weight:700;color:var(--blue)">
            {{ number_format((float) optional($account->balance)->balance, 4) }}
        </div>
        <p class="muted">Credit limit {{ number_format((float) optional($account->balance)->credit_limit, 4) }}
            @if(optional($account->balance)->maxcredit_limit !== null) · max {{ number_format((float) $account->balance->maxcredit_limit, 4) }}@endif
            · account type {{ $account->account_type }}</p>
    </div>

    @can('topUp', $account)
    <div class="card">
        <h2>Manual top-up</h2>
        <form method="POST" action="{{ route('balances.topup', $account) }}">
            @csrf
            {{-- idempotency token: minted server-side per render; a double-submit reuses it and credits once --}}
            <input type="hidden" name="idempotency_key" value="{{ $idemKey }}">
            <div class="grid">
                <div class="field">
                    <label>Amount</label>
                    <input name="amount" inputmode="decimal" placeholder="e.g. 50.00" required>
                </div>
                <div class="field">
                    <label>Notes (optional)</label>
                    <input name="notes" maxlength="255" placeholder="reference / reason">
                </div>
            </div>
            <button class="btn" type="submit">Credit balance</button>
        </form>
    </div>
    @endcan

    <div class="card">
        <h2>Top-up ledger</h2>
        <table>
            <thead><tr><th>When</th><th class="right">Amount</th><th class="right">Before → After</th><th>By</th><th>Notes</th></tr></thead>
            <tbody>
                @forelse($ledger as $l)
                    <tr>
                        <td class="muted">{{ $l->created_at }}</td>
                        <td class="right">{{ number_format((float) $l->amount, 4) }}</td>
                        <td class="right muted">{{ number_format((float) $l->balance_before, 4) }} → {{ number_format((float) $l->balance_after, 4) }}</td>
                        <td>{{ $l->actor }}</td>
                        <td>{{ $l->notes }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No top-ups recorded by this portal yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Payment history (OV500)</h2>
        <table>
            <thead><tr><th>When</th><th>Option</th><th class="right">Amount</th><th>By</th><th>Ref</th></tr></thead>
            <tbody>
                @forelse($history as $h)
                    <tr>
                        <td class="muted">{{ $h->paid_on }}</td>
                        <td>{{ $h->payment_option_id }}</td>
                        <td class="right">{{ number_format((float) $h->amount, 4) }}</td>
                        <td>{{ $h->created_by }}</td>
                        <td class="muted">{{ Str::limit($h->transaction_id, 18) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No payments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
