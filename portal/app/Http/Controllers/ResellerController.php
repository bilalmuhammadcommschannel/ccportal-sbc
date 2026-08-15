<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Account;
use App\Models\Ov500\ResellerProfile;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResellerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Account::class);
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));

        $resellers = Account::query()
            ->where('account_type', 'RESELLER')
            ->with(['resellerRow', 'balance'])
            ->when(! $user->isAdmin(), fn ($x) => $x->whereIn('account_id', $user->accessibleAccountIds()))
            ->when($q !== '', fn ($x) => $x->where('account_id', 'like', "%{$q}%"))
            ->orderBy('account_id')
            ->paginate(25)->withQueryString();

        return view('resellers.index', compact('resellers', 'q'));
    }

    public function create()
    {
        $this->authorize('create', Account::class);
        return view('resellers.form', ['mode' => 'create', 'account' => null, 'profile' => null, 'currencies' => $this->currencies()]);
    }

    public function store(Request $request, AccountService $svc)
    {
        $this->authorize('create', Account::class);
        $data = $this->validated($request);
        $account = $svc->createReseller($data, $request->user());

        return redirect()->route('resellers.show', $account)->with('status', "Reseller {$data['company_name']} created ({$account->account_id}).");
    }

    public function show(Account $reseller)
    {
        abort_unless($reseller->account_type === 'RESELLER', 404);
        $this->authorize('view', $reseller);
        $reseller->load(['resellerRow', 'balance']);
        $subCustomers = DB::connection('switch')->table('account')->where('parent_account_id', $reseller->account_id)->where('account_type', 'CUSTOMER')->count();
        $subResellers = DB::connection('switch')->table('account')->where('parent_account_id', $reseller->account_id)->where('account_type', 'RESELLER')->count();

        return view('resellers.show', compact('reseller', 'subCustomers', 'subResellers'));
    }

    public function edit(Account $reseller)
    {
        abort_unless($reseller->account_type === 'RESELLER', 404);
        $this->authorize('update', $reseller);
        $reseller->load('resellerRow');
        return view('resellers.form', ['mode' => 'edit', 'account' => $reseller, 'profile' => $reseller->resellerRow, 'currencies' => $this->currencies()]);
    }

    public function update(Request $request, Account $reseller)
    {
        abort_unless($reseller->account_type === 'RESELLER', 404);
        $this->authorize('update', $reseller);
        $data = $this->validated($request, $reseller);
        $actor = $request->user()->email;

        DB::connection('switch')->transaction(function () use ($reseller, $data, $actor) {
            $reseller->update([
                'currency_id' => $data['currency_id'],
                'status_id'   => $data['status_id'],
                'update_by'   => $actor,
            ]);
            ResellerProfile::updateOrCreate(
                ['account_id' => $reseller->account_id],
                [
                    'company_name' => $data['company_name'],
                    'contact_name' => $data['contact_name'] ?? null,
                    'phone'        => $data['phone'] ?? null,
                    'emailaddress' => $data['emailaddress'] ?? null,
                ]
            );
        });

        return redirect()->route('resellers.show', $reseller)->with('status', 'Reseller updated.');
    }

    private function currencies()
    {
        return DB::connection('switch')->table('sys_currencies')->orderBy('name')->get();
    }

    private function validated(Request $request, ?Account $reseller = null): array
    {
        return $request->validate([
            'company_name' => ['required', 'string', 'max:50'],
            'contact_name' => ['nullable', 'string', 'max:50'],
            'emailaddress' => ['nullable', 'email', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'currency_id'  => ['required', 'integer', Rule::exists('switch.sys_currencies', 'currency_id')],
            'tax_type'     => ['nullable', Rule::in(['inclusive', 'exclusive'])],
            'status_id'    => [$reseller ? 'required' : 'nullable', Rule::in(['1', '0', '-3'])],
        ]);
    }
}
