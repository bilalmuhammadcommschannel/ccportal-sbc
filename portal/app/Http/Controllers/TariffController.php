<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Ratecard;
use App\Models\Ov500\Tariff;
use App\Models\Ov500\TariffRatecardMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TariffController extends Controller
{
    private const PLATFORM_ACCOUNT = 'SYSTEM'; // OV500 ADMIN_ACCOUNT_ID sentinel

    public function index(Request $request)
    {
        $this->authorize('viewAny', Tariff::class);
        $q = trim((string) $request->query('q', ''));

        $tariffs = Tariff::query()
            ->when($q !== '', fn ($x) => $x->where(fn ($w) =>
                $w->where('tariff_name', 'like', "%{$q}%")->orWhere('tariff_id', 'like', "%{$q}%")))
            ->orderBy('tariff_type')->orderBy('tariff_name')
            ->paginate(25)->withQueryString();

        return view('tariffs.index', compact('tariffs', 'q'));
    }

    public function create()
    {
        $this->authorize('create', Tariff::class);
        return view('tariffs.form', ['tariff' => new Tariff(['tariff_type' => 'CUSTOMER', 'tariff_status' => '1', 'bundle_option' => '0']), 'mode' => 'create']);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Tariff::class);
        $data = $this->validated($request);
        $actor = $request->user()->email;

        $tariff = new Tariff($data);
        $tariff->tariff_id   = $this->uniqueKey('tariff', 'tariff_id', 'TAR');
        $tariff->account_id  = $request->user()->account_id ?: self::PLATFORM_ACCOUNT;
        $tariff->created_by  = $actor;
        $tariff->updated_by  = $actor;
        $tariff->create_dt   = now();
        $tariff->update_dt   = now();
        $tariff->save();

        return redirect()->route('tariffs.show', $tariff)->with('status', "Tariff {$tariff->tariff_name} created.");
    }

    public function show(Tariff $tariff)
    {
        $this->authorize('view', $tariff);
        $tariff->load(['maps' => fn ($q) => $q->orderBy('ratecard_for')->orderBy('priority'), 'maps.ratecard']);
        // ratecards of the same type not yet attached, offered for attaching
        $attachable = Ratecard::where('ratecard_type', $tariff->tariff_type)
            ->whereNotIn('ratecard_id', $tariff->maps->pluck('ratecard_id'))
            ->orderBy('ratecard_name')->get();

        return view('tariffs.show', compact('tariff', 'attachable'));
    }

    public function edit(Tariff $tariff)
    {
        $this->authorize('update', $tariff);
        return view('tariffs.form', ['tariff' => $tariff, 'mode' => 'edit']);
    }

    public function update(Request $request, Tariff $tariff)
    {
        $this->authorize('update', $tariff);
        $data = $this->validated($request, $tariff);
        $tariff->fill($data);
        $tariff->updated_by = $request->user()->email;
        $tariff->update_dt  = now();
        $tariff->save();

        return redirect()->route('tariffs.show', $tariff)->with('status', 'Tariff updated.');
    }

    public function attachRatecard(Request $request, Tariff $tariff)
    {
        $this->authorize('update', $tariff);
        $data = $request->validate([
            'ratecard_id' => ['required', Rule::exists('switch.ratecard', 'ratecard_id')->where('ratecard_type', $tariff->tariff_type)],
            'ratecard_for'=> ['required', Rule::in(['INCOMING', 'OUTGOING'])],
            'priority'    => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        TariffRatecardMap::create([
            'ratecard_id' => $data['ratecard_id'],
            'tariff_id'   => $tariff->tariff_id,
            'start_day'   => 0, 'start_time' => '00:00:00',
            'end_day'     => 6, 'end_time' => '23:59:59',
            'priority'    => $data['priority'],
            'status'      => '1',
            'ratecard_for'=> $data['ratecard_for'],
            'account_id'  => $request->user()->account_id ?: self::PLATFORM_ACCOUNT,
            'created_by'  => $request->user()->email,
            'created_dt'  => now(),
        ]);

        return back()->with('status', 'Ratecard attached.');
    }

    public function detachRatecard(Tariff $tariff, TariffRatecardMap $map)
    {
        $this->authorize('update', $tariff);
        abort_unless($map->tariff_id === $tariff->tariff_id, 404);
        $map->delete();

        return back()->with('status', 'Ratecard detached.');
    }

    private function validated(Request $request, ?Tariff $tariff = null): array
    {
        return $request->validate([
            'tariff_name'        => ['required', 'string', 'max:30'],
            'tariff_type'        => ['required', Rule::in(['CUSTOMER', 'CARRIER'])],
            'tariff_status'      => ['required', Rule::in(['0', '1'])],
            'tariff_currency_id' => ['required', 'integer', 'min:1'],
            'tariff_description' => ['nullable', 'string', 'max:50'],
            'monthly_charges'    => ['nullable', 'numeric', 'min:0'],
            'package_option'     => ['required', Rule::in(['0', '1'])],
            'bundle_option'      => ['required', Rule::in(['0', '1'])],
            'bundle1_type'       => ['nullable', Rule::in(['MINUTE', 'COST'])],
            'bundle1_value'      => ['nullable', 'numeric', 'min:0'],
            'bundle2_type'       => ['nullable', Rule::in(['MINUTE', 'COST'])],
            'bundle2_value'      => ['nullable', 'numeric', 'min:0'],
            'bundle3_type'       => ['nullable', Rule::in(['MINUTE', 'COST'])],
            'bundle3_value'      => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function uniqueKey(string $table, string $col, string $prefix): string
    {
        do {
            $key = $prefix . strtoupper(Str::random(6));
        } while (DB::connection('switch')->table($table)->where($col, $key)->exists());
        return $key;
    }
}
