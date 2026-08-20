<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Account;
use App\Models\Ov500\BundleAccount;
use App\Models\Ov500\BundlePackage;
use App\Models\Ov500\BundlePackagePrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BundleController extends Controller
{
    private const PLATFORM = 'SYSTEM';

    public function index(Request $request)
    {
        $this->authorize('viewAny', BundlePackage::class);
        $q = trim((string) $request->query('q', ''));

        $bundles = BundlePackage::query()
            ->withCount('assignments')
            ->when($q !== '', fn ($x) => $x->where(fn ($w) =>
                $w->where('bundle_package_name', 'like', "%{$q}%")->orWhere('bundle_package_id', 'like', "%{$q}%")))
            ->orderBy('bundle_package_name')
            ->paginate(25)->withQueryString();

        return view('bundles.index', compact('bundles', 'q'));
    }

    public function create()
    {
        $this->authorize('create', BundlePackage::class);
        return view('bundles.form', ['mode' => 'create', 'bundle' => new BundlePackage(['bundle_package_status' => '1', 'bundle_option' => '1', 'package_option' => '0', 'bundle1_type' => 'MINUTE', 'bundle2_type' => 'MINUTE', 'bundle3_type' => 'MINUTE']), 'currencies' => $this->currencies()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', BundlePackage::class);
        $data = $this->validated($request);
        $actor = $request->user()->email;

        $bundle = new BundlePackage($data);
        $bundle->bundle_package_id = $this->uniqueKey();
        $bundle->account_id = $request->user()->account_id ?: self::PLATFORM;
        $bundle->created_by = $actor;
        $bundle->updated_by = $actor;
        $bundle->create_dt = now();
        $bundle->update_dt = now();
        $bundle->save();

        return redirect()->route('bundles.show', $bundle)->with('status', "Bundle {$bundle->bundle_package_name} created.");
    }

    public function show(BundlePackage $bundle)
    {
        $this->authorize('view', $bundle);
        $bundle->load(['prefixes', 'assignments']);
        // resolve assigned account names
        $accountNames = Account::whereIn('account_id', $bundle->assignments->pluck('account_id'))
            ->with(['customerRow', 'resellerRow'])->get()->keyBy('account_id');
        // accounts available to assign
        $assignable = Account::query()->orderBy('account_id')->limit(500)->get();

        return view('bundles.show', compact('bundle', 'accountNames', 'assignable'));
    }

    public function edit(BundlePackage $bundle)
    {
        $this->authorize('update', $bundle);
        return view('bundles.form', ['mode' => 'edit', 'bundle' => $bundle, 'currencies' => $this->currencies()]);
    }

    /** Currency options for the form (id + name + symbol). */
    private function currencies()
    {
        return \Illuminate\Support\Facades\DB::connection('switch')->table('sys_currencies')->orderBy('name')->get();
    }

    public function update(Request $request, BundlePackage $bundle)
    {
        $this->authorize('update', $bundle);
        $data = $this->validated($request, $bundle);
        $bundle->fill($data);
        $bundle->updated_by = $request->user()->email;
        $bundle->update_dt = now();
        $bundle->save();

        return redirect()->route('bundles.show', $bundle)->with('status', 'Bundle updated.');
    }

    /** Add a prefix to a tier. */
    public function addPrefix(Request $request, BundlePackage $bundle)
    {
        $this->authorize('update', $bundle);
        $data = $request->validate([
            'bundle_id' => ['required', Rule::in(['1', '2', '3'])],
            'prefix'    => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
        ]);
        BundlePackagePrefix::firstOrCreate([
            'bundle_package_id' => $bundle->bundle_package_id,
            'prefix'            => $data['prefix'],
        ], ['bundle_id' => $data['bundle_id']]);

        return back()->with('status', "Prefix {$data['prefix']} added to tier {$data['bundle_id']}.");
    }

    public function removePrefix(BundlePackage $bundle, BundlePackagePrefix $prefix)
    {
        $this->authorize('update', $bundle);
        abort_unless($prefix->bundle_package_id === $bundle->bundle_package_id, 404);
        $prefix->delete();

        return back()->with('status', 'Prefix removed.');
    }

    /** Assign the bundle to an account (idempotent per account_bundle_key). */
    public function assign(Request $request, BundlePackage $bundle)
    {
        $this->authorize('update', $bundle);
        $data = $request->validate([
            'account_id' => ['required', Rule::exists('switch.account', 'account_id')],
        ]);
        $actor = $request->user()->email;

        $key = $bundle->bundle_package_id . ':' . $data['account_id'];
        BundleAccount::firstOrCreate(
            ['account_bundle_key' => $key],
            [
                'bundle_package_id'   => $bundle->bundle_package_id,
                'account_id'          => $data['account_id'],
                'assign_dt'           => now()->toDateString(),
                'bundle_package_desc' => Str::limit($bundle->bundle_package_name, 50, ''),
                'created_by'          => $actor,
                'created_dt'          => now(),
            ]
        );

        return back()->with('status', "Assigned to {$data['account_id']}.");
    }

    public function unassign(BundlePackage $bundle, BundleAccount $assignment)
    {
        $this->authorize('update', $bundle);
        abort_unless($assignment->bundle_package_id === $bundle->bundle_package_id, 404);
        $assignment->delete();

        return back()->with('status', 'Unassigned.');
    }

    private function validated(Request $request, ?BundlePackage $bundle = null): array
    {
        return $request->validate([
            'bundle_package_name'        => ['required', 'string', 'max:30'],
            'bundle_package_currency_id' => ['required', 'integer', 'min:1'],
            'bundle_package_status'      => ['required', Rule::in(['0', '1'])],
            'bundle_package_description'  => ['nullable', 'string', 'max:50'],
            'monthly_charges'            => ['nullable', 'numeric', 'min:0'],
            'package_option'             => ['required', Rule::in(['0', '1'])],
            'bundle_option'              => ['required', Rule::in(['0', '1'])],
            'bundle1_type'  => ['required', Rule::in(['MINUTE', 'COST'])],
            'bundle1_value' => ['nullable', 'numeric', 'min:0'],
            'bundle2_type'  => ['required', Rule::in(['MINUTE', 'COST'])],
            'bundle2_value' => ['nullable', 'numeric', 'min:0'],
            'bundle3_type'  => ['required', Rule::in(['MINUTE', 'COST'])],
            'bundle3_value' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function uniqueKey(): string
    {
        do {
            $key = 'BP' . strtoupper(Str::random(6));
        } while (DB::connection('switch')->table('bundle_package')->where('bundle_package_id', $key)->exists());
        return $key;
    }
}
