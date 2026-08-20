<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Account;
use App\Models\Ov500\CustomerProfile;
use App\Models\Ov500\CustomerVoipMinute;
use App\Models\Ov500\Tariff;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Account::class);
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        // default view is active-only; 'all' shows every status (incl. Closed/Inactive)
        $status = $request->query('status') === 'all' ? 'all' : 'active';

        $customers = Account::query()
            ->where('account_type', 'CUSTOMER')
            ->with(['customerRow', 'balance'])
            ->when(! $user->isAdmin(), fn ($x) => $x->whereIn('account_id', $user->accessibleAccountIds()))
            ->when($status === 'active', fn ($x) => $x->where('status_id', '1'))
            ->when($q !== '', fn ($x) => $x->where('account_id', 'like', "%{$q}%"))
            ->orderBy('account_id')
            ->paginate(25)->withQueryString();

        return view('customers.index', compact('customers', 'q', 'status'));
    }

    public function create()
    {
        $this->authorize('create', Account::class);
        return view('customers.form', ['mode' => 'create', 'account' => null, 'profile' => null, 'tariffId' => null, 'tariffs' => $this->customerTariffs(), 'currencies' => $this->currencies()]);
    }

    public function store(Request $request, AccountService $svc)
    {
        $this->authorize('create', Account::class);
        $data = $this->validated($request);
        $account = $svc->createCustomer($data, $request->user());

        // The SIP secret is shown ONCE here (it is masked everywhere after) — the
        // operator must copy it now to hand to the customer / provision the phone.
        return redirect()->route('customers.show', $account)
            ->with('status', "Customer {$data['company_name']} created ({$account->account_id}).")
            ->with('new_endpoint', [
                'username' => $account->new_endpoint_username ?? null,
                'secret'   => $account->new_endpoint_secret ?? null,
            ]);
    }

    public function show(Account $customer)
    {
        abort_unless($customer->account_type === 'CUSTOMER', 404);
        $this->authorize('view', $customer);
        $customer->load(['customerRow', 'balance']);
        $tariff = $this->currentTariff($customer->account_id);
        $endpoints = DB::connection('switch')->table('customer_sip_account')->where('account_id', $customer->account_id)->count();
        $dids = DB::connection('switch')->table('did')->where('account_id', $customer->account_id)->count();

        return view('customers.show', compact('customer', 'tariff', 'endpoints', 'dids'));
    }

    public function edit(Account $customer)
    {
        abort_unless($customer->account_type === 'CUSTOMER', 404);
        $this->authorize('update', $customer);
        $customer->load('customerRow');

        return view('customers.form', [
            'mode' => 'edit',
            'account' => $customer,
            'profile' => $customer->customerRow,
            'tariffId' => optional($this->currentTariff($customer->account_id))->tariff_id,
            'tariffs' => $this->customerTariffs(),
            'currencies' => $this->currencies(),
        ]);
    }

    public function update(Request $request, Account $customer, AccountService $svc)
    {
        abort_unless($customer->account_type === 'CUSTOMER', 404);
        $this->authorize('update', $customer);
        $data = $this->validated($request, $customer);
        $actor = $request->user()->email;

        DB::connection('switch')->transaction(function () use ($customer, $data, $actor, $svc) {
            $customer->update([
                'currency_id' => $data['currency_id'],
                'account_cc'  => $data['account_cc'] ?? $customer->account_cc,
                'account_cps' => $data['account_cps'] ?? $customer->account_cps,
                'status_id'   => $data['status_id'],
                'update_by'   => $actor,
            ]);
            CustomerProfile::updateOrCreate(
                ['account_id' => $customer->account_id],
                [
                    'company_name'  => $data['company_name'],
                    'contact_name'  => $data['contact_name'] ?? null,
                    'phone'         => $data['phone'] ?? null,
                    'emailaddress'  => $data['emailaddress'] ?? null,
                    'billing_type'  => $data['billing_type'],
                    'billing_cycle' => $data['billing_cycle'],
                    'updated_by'    => $actor,
                ]
            );
            if (! empty($data['tariff_id'])) {
                $svc->assignTariff($customer->account_id, 'CUSTOMER', $data['tariff_id'], $actor);
            }
        });

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated.');
    }

    public function destroy(Request $request, Account $customer, AccountService $svc)
    {
        abort_unless($customer->account_type === 'CUSTOMER', 404);
        $this->authorize('delete', $customer);

        $result = $svc->deleteCustomer($customer, $request->user());

        if ($result['action'] === 'deleted') {
            return redirect()->route('customers.index')
                ->with('status', "Customer {$customer->account_id} permanently deleted — it had no dependents or history.");
        }

        return redirect()->route('customers.show', $customer)
            ->with('status', "Customer {$customer->account_id} archived (Closed) and its service disabled — kept because it has "
                . implode(', ', $result['blockers']) . ". All records preserved.");
    }

    private function validated(Request $request, ?Account $customer = null): array
    {
        return $request->validate([
            'company_name'  => ['required', 'string', 'max:50'],
            'contact_name'  => ['nullable', 'string', 'max:150'],
            'emailaddress'  => ['nullable', 'email', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'currency_id'   => ['required', 'integer', Rule::exists('switch.sys_currencies', 'currency_id')],
            'billing_type'  => ['required', Rule::in(['prepaid', 'postpaid', 'netoff'])],
            'billing_cycle' => ['required', Rule::in(['weekly', 'monthly'])],
            'account_cc'    => ['nullable', 'integer', 'min:0', 'max:100000'],
            'account_cps'   => ['nullable', 'integer', 'min:0', 'max:100000'],
            'tax_type'      => ['nullable', Rule::in(['inclusive', 'exclusive'])],
            'tariff_id'     => ['nullable', Rule::exists('switch.tariff', 'tariff_id')->where('tariff_type', 'CUSTOMER')],
            'status_id'     => [$customer ? 'required' : 'nullable', Rule::in(['1', '0', '-3'])],
        ]);
    }

    private function customerTariffs()
    {
        return Tariff::where('tariff_type', 'CUSTOMER')->where('tariff_status', '1')->orderBy('tariff_name')->pluck('tariff_name', 'tariff_id');
    }

    private function currencies()
    {
        return DB::connection('switch')->table('sys_currencies')->orderBy('name')->get();
    }

    private function currentTariff(string $accountId): ?CustomerVoipMinute
    {
        return CustomerVoipMinute::where('account_id', $accountId)->first();
    }
}
