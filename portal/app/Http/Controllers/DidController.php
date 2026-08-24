<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Account;
use App\Models\Ov500\Did;
use App\Models\Ov500\DidDestination;
use App\Models\Ov500\SipAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DidController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Did::class);
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status', '');

        $dids = Did::query()
            ->when(! $user->isAdmin(), function ($query) use ($user) {
                $ids = $user->accessibleAccountIds();
                $query->where(fn ($w) => $w->whereIn('account_id', $ids)
                    ->orWhereIn('reseller1_account_id', $ids)
                    ->orWhereIn('reseller2_account_id', $ids)
                    ->orWhereIn('reseller3_account_id', $ids));
            })
            ->when($q !== '', fn ($x) => $x->where('did_number', 'like', "%{$q}%"))
            ->when(in_array($status, ['NEW','USED','DEAD','BLOCKED'], true), fn ($x) => $x->where('did_status', $status))
            ->orderBy('did_number')
            ->paginate(30)->withQueryString();

        return view('dids.index', compact('dids', 'q', 'status'));
    }

    public function create()
    {
        $this->authorize('create', Did::class);
        return view('dids.create');
    }

    /** Add one number or a contiguous range to inventory (status NEW). */
    public function store(Request $request)
    {
        $this->authorize('create', Did::class);
        $data = $request->validate([
            'mode'        => ['required', Rule::in(['single', 'range'])],
            'did_number'  => ['required_if:mode,single', 'nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'range_from'  => ['required_if:mode,range', 'nullable', 'regex:/^[0-9]+$/'],
            'range_to'    => ['required_if:mode,range', 'nullable', 'regex:/^[0-9]+$/'],
            'number_type' => ['required', Rule::in(['DID', 'TFN'])],
            'channels'    => ['required', 'integer', 'min:1', 'max:10000'],
            'did_name'    => ['nullable', 'string', 'max:150'],
        ]);

        $numbers = [];
        if ($data['mode'] === 'single') {
            $numbers[] = $data['did_number'];
        } else {
            $from = $data['range_from']; $to = $data['range_to'];
            if (strlen($from) !== strlen($to) || bccomp($to, $from) < 0) {
                return back()->withErrors(['range_to' => 'Range must be same length and ascending.'])->withInput();
            }
            if ((int) bcsub($to, $from) > 1000) {
                return back()->withErrors(['range_to' => 'Range too large (max 1000 numbers per batch).'])->withInput();
            }
            for ($n = $from; bccomp($n, $to) <= 0; $n = bcadd($n, '1')) {
                $numbers[] = str_pad($n, strlen($from), '0', STR_PAD_LEFT);
            }
        }

        $created = 0; $skipped = 0;
        DB::connection('switch')->transaction(function () use ($numbers, $data, &$created, &$skipped) {
            foreach ($numbers as $num) {
                if (Did::where('did_number', $num)->exists()) { $skipped++; continue; }
                Did::create([
                    'did_number' => $num,
                    'did_status' => 'NEW',
                    'number_type'=> $data['number_type'],
                    'channels'   => $data['channels'],
                    'did_name'   => $data['did_name'] ?? null,
                    'create_date'=> now(),
                ]);
                $created++;
            }
        });

        return redirect()->route('dids.index')
            ->with('status', "Added {$created} number(s)." . ($skipped ? " {$skipped} already existed." : ''));
    }

    public function show(Did $did)
    {
        $this->authorize('view', $did);
        $did->load('destination');
        return view('dids.show', compact('did'));
    }

    public function edit(Did $did)
    {
        $this->authorize('assign', $did);
        $did->load('destination');
        // accounts the actor may assign to
        $user = request()->user();
        $accounts = Account::query()
            ->when(! $user->isAdmin(), fn ($x) => $x->whereIn('account_id', $user->accessibleAccountIds()))
            ->orderBy('account_id')->limit(500)->get();
        // endpoints per account (for the routing-destination picker): choosing an
        // account reveals that customer's SIP usernames as selectable routing targets.
        $endpointsByAccount = SipAccount::query()
            ->when(! $user->isAdmin(), fn ($x) => $x->whereIn('account_id', $user->accessibleAccountIds()))
            ->orderBy('username')->get(['username', 'account_id'])
            ->groupBy('account_id')->map(fn ($g) => $g->pluck('username')->values())->toArray();
        // provider carriers (who delivers this DID's inbound calls) — used to
        // attribute inbound CDRs to the right trunk. Carrier inventory is
        // admin-only, so resellers get no list (and the field is hidden).
        $carriers = $user->isAdmin()
            ? DB::connection('switch')->table('carrier')->orderBy('carrier_name')->get(['carrier_id', 'carrier_name', 'carrier_type'])
            : collect();
        // searchable account options showing the company name (grows over time)
        $custNames = DB::connection('switch')->table('customers')->pluck('company_name', 'account_id');
        $resNames  = DB::connection('switch')->table('resellers')->pluck('company_name', 'account_id');
        $accountOptions = $accounts->map(function ($a) use ($custNames, $resNames) {
            $name = $custNames[$a->account_id] ?? $resNames[$a->account_id] ?? null;
            return [
                'id'    => $a->account_id,
                'label' => ($name ? $name . ' — ' : '') . $a->account_id . ' (' . $a->account_type . ')',
            ];
        })->values();
        return view('dids.edit', compact('did', 'accounts', 'endpointsByAccount', 'carriers', 'accountOptions'));
    }

    /** Assign to an account + set channels/name/routing destination. */
    public function update(Request $request, Did $did)
    {
        $this->authorize('assign', $did);
        $data = $request->validate([
            'account_id'       => ['nullable', Rule::exists('switch.account', 'account_id')],
            'carrier_id'       => ['nullable', Rule::exists('switch.carrier', 'carrier_id')],
            'channels'         => ['required', 'integer', 'min:1', 'max:10000'],
            'did_name'         => ['nullable', 'string', 'max:150'],
            'did_status'       => ['required', Rule::in(['NEW', 'USED', 'DEAD', 'BLOCKED'])],
            'dst_type'         => ['nullable', Rule::in(['IP', 'CUSTOMER', 'PSTN'])],
            'dst_destination'  => ['nullable', 'string', 'max:30'],
            'dst_type2'        => ['nullable', Rule::in(['IP', 'CUSTOMER', 'PSTN'])],
            'dst_destination2' => ['nullable', 'string', 'max:30'],
        ]);

        // a reseller may only assign to accounts in their own subtree
        if ($data['account_id'] && ! $request->user()->isAdmin()
            && ! in_array($data['account_id'], $request->user()->accessibleAccountIds(), true)) {
            return back()->withErrors(['account_id' => 'That account is outside your scope.'])->withInput();
        }

        // unassigning / downgrading an assigned DID is a release — admin-only per DidPolicy,
        // so it cannot be done through the update path by a reseller.
        $isRelease = (empty($data['account_id']) && ! empty($did->account_id))
            || ($data['did_status'] === 'NEW' && $did->did_status !== 'NEW' && ! empty($did->account_id));
        if ($isRelease) {
            $this->authorize('release', $did);
        }

        DB::connection('switch')->transaction(function () use ($did, $data, $request) {
            $wasUnassigned = empty($did->account_id);
            $did->channels    = $data['channels'];
            $did->did_name    = $data['did_name'] ?? $did->did_name;
            $did->did_status  = $data['did_status'];
            // carrier is a platform-global resource — only admins may set it
            if ($request->user()->isAdmin() && array_key_exists('carrier_id', $data)) {
                $did->carrier_id = $data['carrier_id'] ?: null;
            }
            if (array_key_exists('account_id', $data)) {
                $did->account_id = $data['account_id'] ?: null;
                if ($did->account_id && $wasUnassigned) {
                    $did->assign_date = now();
                    if ($did->did_status === 'NEW') $did->did_status = 'USED';
                }
            }
            $did->save();

            if (! empty($data['dst_type']) && ! empty($data['dst_destination'])) {
                DidDestination::updateOrCreate(
                    ['did_number' => $did->did_number],
                    [
                        'account_id'       => $did->account_id,
                        'dst_type'         => $data['dst_type'],
                        'dst_destination'  => $data['dst_destination'],
                        'dst_type2'        => $data['dst_type2'] ?? null,
                        'dst_destination2' => $data['dst_destination2'] ?? null,
                        'create_date'      => now(),
                    ]
                );
            }
        });

        return redirect()->route('dids.show', $did)->with('status', 'DID updated.');
    }

    /** Release: unassign from the account, back to NEW inventory. */
    public function release(Did $did)
    {
        $this->authorize('release', $did);
        DB::connection('switch')->transaction(function () use ($did) {
            $did->update([
                'account_id' => null, 'assign_date' => null,
                'reseller1_account_id' => null, 'reseller2_account_id' => null, 'reseller3_account_id' => null,
                'did_status' => 'NEW',
            ]);
            DidDestination::where('did_number', $did->did_number)->delete();
        });

        return redirect()->route('dids.index')->with('status', "DID {$did->did_number} released to inventory.");
    }
}
