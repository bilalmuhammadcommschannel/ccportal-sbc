<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Carrier;
use App\Models\Ov500\CarrierIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CarrierController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Carrier::class);

        // Parameterised search (bound, never concatenated). Query builder escapes.
        $q = trim((string) $request->query('q', ''));

        $carriers = Carrier::query()
            ->withCount('ips')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('carrier_name', 'like', "%{$q}%")
                      ->orWhere('carrier_id', 'like', "%{$q}%");
                });
            })
            ->orderBy('carrier_name')
            ->paginate(25)
            ->withQueryString();

        return view('carriers.index', compact('carriers', 'q'));
    }

    public function create()
    {
        $this->authorize('create', Carrier::class);

        return view('carriers.form', [
            'carrier'  => new Carrier(['carrier_type' => 'OUTBOUND', 'carrier_status' => 1]),
            'ips'      => collect([new CarrierIp(['auth_type' => 'IP', 'ip_status' => '1', 'load_share' => 1, 'priority' => 1])]),
            'tariffs'  => $this->tariffOptions(),
            'mode'     => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Carrier::class);

        $data = $this->validateCarrier($request);
        $actor = $request->user()->email;

        $carrier = DB::connection('switch')->transaction(function () use ($data, $actor, $request) {
            $carrierId = $this->uniqueBusinessKey('carrier', 'carrier_id', 'CC');

            $carrier = new Carrier();
            $carrier->fill($data['carrier']);
            $carrier->carrier_id = $carrierId;
            $carrier->account_id = $request->user()->account_id; // tenancy stamp
            $carrier->created_by = $actor;
            $carrier->updated_by = $actor;
            $carrier->created_dt = now();
            $carrier->updated_dt = now();
            $carrier->save();

            $this->syncIps($carrier, $data['ips'], $actor, $request);

            return $carrier;
        });

        return redirect()
            ->route('carriers.show', $carrier)
            ->with('status', "Carrier {$carrier->carrier_name} created.");
    }

    public function show(Carrier $carrier)
    {
        $this->authorize('view', $carrier);
        $carrier->load('ips', 'prefixes');

        return view('carriers.show', compact('carrier'));
    }

    public function edit(Carrier $carrier)
    {
        $this->authorize('update', $carrier);
        $carrier->load('ips');

        return view('carriers.form', [
            'carrier' => $carrier,
            'ips'     => $carrier->ips->isNotEmpty() ? $carrier->ips : collect([new CarrierIp(['auth_type' => 'IP'])]),
            'tariffs' => $this->tariffOptions(),
            'mode'    => 'edit',
        ]);
    }

    public function update(Request $request, Carrier $carrier)
    {
        $this->authorize('update', $carrier);

        $data = $this->validateCarrier($request, $carrier);
        $actor = $request->user()->email;

        DB::connection('switch')->transaction(function () use ($carrier, $data, $actor, $request) {
            $carrier->fill($data['carrier']);
            $carrier->updated_by = $actor;
            $carrier->updated_dt = now();
            $carrier->save();

            $this->syncIps($carrier, $data['ips'], $actor, $request);
        });

        return redirect()
            ->route('carriers.show', $carrier)
            ->with('status', "Carrier {$carrier->carrier_name} updated.");
    }

    // ---- helpers -----------------------------------------------------------

    private function validateCarrier(Request $request, ?Carrier $carrier = null): array
    {
        // Drop entirely-blank endpoint rows (the form always renders one spare
        // blank row for adding another endpoint without JS). Re-index so the
        // ips.* validation rules and error messages line up.
        $ips = collect((array) $request->input('ips', []))
            ->reject(fn ($row) => blank($row['ipaddress_name'] ?? null) && blank($row['ipaddress'] ?? null))
            ->values()
            ->all();
        $request->merge(['ips' => $ips]);

        $validated = $request->validate([
            'carrier_name'   => ['required', 'string', 'max:30'],
            'tariff_id'      => ['required', 'string', 'max:30', Rule::exists('switch.tariff', 'tariff_id')],
            'carrier_type'   => ['required', Rule::in(['INBOUND', 'OUTBOUND'])],
            'carrier_status' => ['required', Rule::in(['0', '1'])],
            'carrier_cps'    => ['nullable', 'integer', 'min:0', 'max:100000'],
            'carrier_cc'     => ['nullable', 'integer', 'min:0', 'max:100000'],
            'cli_prefer'     => ['required', Rule::in(['rpid', 'pid', 'no'])],
            'carrier_codecs' => ['nullable', 'string', 'max:50'],
            'tax_type'       => ['required', Rule::in(['inclusive', 'exclusive'])],

            'ips'                    => ['required', 'array', 'min:1', 'max:20'],
            'ips.*.ipaddress_name'   => ['required', 'string', 'max:30'],
            'ips.*.ipaddress'        => ['required', 'ip'],
            'ips.*.auth_type'        => ['required', Rule::in(['IP', 'CUSTOMER'])],
            'ips.*.username'         => ['nullable', 'required_if:ips.*.auth_type,CUSTOMER', 'string', 'max:50'],
            'ips.*.passwd'           => ['nullable', 'required_if:ips.*.auth_type,CUSTOMER', 'string', 'max:50'],
            'ips.*.load_share'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'ips.*.priority'         => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        return [
            'carrier' => [
                'carrier_name'   => $validated['carrier_name'],
                'tariff_id'      => $validated['tariff_id'],
                'carrier_type'   => $validated['carrier_type'],
                'carrier_status' => (int) $validated['carrier_status'],
                'carrier_cps'    => $validated['carrier_cps'] ?? 0,
                'carrier_cc'     => $validated['carrier_cc'] ?? 0,
                'cli_prefer'     => $validated['cli_prefer'],
                'carrier_codecs' => $validated['carrier_codecs'] ?? 'PCMU,PCMA',
                'tax_type'       => $validated['tax_type'],
            ],
            'ips' => $validated['ips'],
        ];
    }

    /**
     * Replace the carrier's IP set with the submitted one, inside the caller's
     * transaction. A blank passwd on an existing CUSTOMER row keeps the stored
     * secret (validation already required one on create).
     */
    private function syncIps(Carrier $carrier, array $ips, string $actor, Request $request): void
    {
        $existingByName = $carrier->ips()->get()->keyBy('ipaddress_name');
        $keptNames = [];

        foreach ($ips as $row) {
            $name = $row['ipaddress_name'];
            $keptNames[] = $name;
            $existing = $existingByName->get($name);

            $attrs = [
                'ipaddress'  => $row['ipaddress'],
                'auth_type'  => $row['auth_type'],
                'username'   => $row['auth_type'] === 'CUSTOMER' ? ($row['username'] ?? null) : null,
                'load_share' => $row['load_share'] ?? 1,
                'priority'   => $row['priority'] ?? 1,
                'ip_status'  => '1',
                'updated_by' => $actor,
                'updated_dt' => now(),
            ];

            // only overwrite the secret when a new one was actually supplied
            if ($row['auth_type'] === 'CUSTOMER' && !empty($row['passwd'])) {
                $attrs['passwd'] = $row['passwd'];
            } elseif ($row['auth_type'] === 'IP') {
                $attrs['passwd'] = null;
            }

            if ($existing) {
                $existing->fill($attrs)->save();
            } else {
                $ip = new CarrierIp();
                $ip->fill($attrs);
                $ip->carrier_ip_id = $this->uniqueBusinessKey('carrier_ips', 'carrier_ip_id', 'CIP');
                $ip->carrier_id = $carrier->carrier_id;
                $ip->ipaddress_name = $name;
                $ip->account_id = $carrier->account_id;
                $ip->created_by = $actor;
                $ip->created_dt = now();
                if ($row['auth_type'] === 'CUSTOMER') {
                    $ip->passwd = $row['passwd'] ?? null;
                }
                $ip->save();
            }
        }

        // remove IPs the operator deleted from the form
        $carrier->ips()->whereNotIn('ipaddress_name', $keptNames)->delete();
    }

    private function tariffOptions()
    {
        return DB::connection('switch')->table('tariff')
            ->where('tariff_type', 'CARRIER')
            ->orderBy('tariff_name')
            ->pluck('tariff_name', 'tariff_id');
    }

    private function uniqueBusinessKey(string $table, string $column, string $prefix): string
    {
        do {
            $key = $prefix . strtoupper(Str::random(8));
            $exists = DB::connection('switch')->table($table)->where($column, $key)->exists();
        } while ($exists);

        return $key;
    }
}
