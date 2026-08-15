<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Account;
use App\Models\Ov500\BalanceLedger;
use App\Models\Ov500\PaymentHistory;
use App\Services\BalanceService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Account::class);
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));

        $accounts = Account::query()
            ->with(['balance', 'customerRow', 'resellerRow'])
            // tenancy: admin sees all; reseller sees only their subtree
            ->when(! $user->isAdmin(), fn ($query) => $query->whereIn('account_id', $user->accessibleAccountIds()))
            ->when($q !== '', fn ($query) => $query->where('account_id', 'like', "%{$q}%"))
            ->orderBy('account_id')
            ->paginate(25)
            ->withQueryString();

        return view('balances.index', compact('accounts', 'q'));
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);
        $account->load(['balance', 'customerRow', 'resellerRow']);

        $history = PaymentHistory::where('account_id', $account->account_id)
            ->orderByDesc('payment_id')
            ->limit(50)
            ->get();

        $ledger = BalanceLedger::where('account_id', $account->account_id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        // Idempotency token minted per form render; carried as a hidden field.
        $idemKey = (string) Str::uuid();

        return view('balances.show', compact('account', 'history', 'ledger', 'idemKey'));
    }

    public function topUp(Request $request, Account $account, BalanceService $svc)
    {
        // Authorize the SPECIFIC account before any work (not after).
        $this->authorize('topUp', $account);

        $data = $request->validate([
            // decimal string, > 0, capped; never a float, never trusted blindly
            'amount'          => ['required', 'regex:/^\d{1,9}(\.\d{1,6})?$/'],
            'notes'           => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        if (bccomp($data['amount'], '0', 6) <= 0) {
            return back()->withErrors(['amount' => 'Amount must be greater than zero.'])->withInput();
        }
        if (bccomp($data['amount'], '1000000', 6) > 0) {
            return back()->withErrors(['amount' => 'Amount exceeds the single-transaction limit.'])->withInput();
        }

        try {
            $ledger = $svc->topUp(
                account: $account,
                amount: $data['amount'],
                idempotencyKey: $data['idempotency_key'],
                actor: $request->user()->email,
                notes: $data['notes'] ?? null,
            );
        } catch (QueryException $e) {
            // Unique idempotency_key violation from a genuine race — already applied.
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return redirect()->route('balances.show', $account)
                    ->with('status', 'That top-up was already applied (duplicate submission ignored).');
            }
            throw $e;
        }

        return redirect()->route('balances.show', $account)->with(
            'status',
            "Credited {$ledger->amount}. Balance {$ledger->balance_before} → {$ledger->balance_after}."
        );
    }
}
