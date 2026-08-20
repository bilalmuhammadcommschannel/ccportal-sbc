<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Ratecard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RatecardController extends Controller
{
    private const PLATFORM_ACCOUNT = 'SYSTEM';

    public function index(Request $request)
    {
        $this->authorize('viewAny', Ratecard::class);
        $q = trim((string) $request->query('q', ''));

        $ratecards = Ratecard::query()
            ->when($q !== '', fn ($x) => $x->where(fn ($w) =>
                $w->where('ratecard_name', 'like', "%{$q}%")->orWhere('ratecard_id', 'like', "%{$q}%")))
            ->orderBy('ratecard_type')->orderBy('ratecard_name')
            ->paginate(25)->withQueryString();

        return view('ratecards.index', compact('ratecards', 'q'));
    }

    public function create()
    {
        $this->authorize('create', Ratecard::class);
        return view('ratecards.form', ['ratecard' => new Ratecard(['ratecard_type' => 'CUSTOMER', 'ratecard_for' => 'OUTGOING']), 'mode' => 'create', 'currencies' => $this->currencies()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ratecard::class);
        $data = $this->validated($request);
        $actor = $request->user()->email;

        $rc = new Ratecard($data);
        $rc->ratecard_id = $this->uniqueKey('RC');
        $rc->account_id  = $request->user()->account_id ?: self::PLATFORM_ACCOUNT;
        $rc->created_by  = $actor;
        $rc->updated_by  = $actor;
        $rc->created_dt  = now();
        $rc->updated_dt  = now();
        $rc->save();

        return redirect()->route('ratecards.show', $rc)->with('status', "Ratecard {$rc->ratecard_name} created.");
    }

    public function show(Request $request, Ratecard $ratecard)
    {
        $this->authorize('view', $ratecard);
        $q = trim((string) $request->query('q', ''));

        $rates = $ratecard->rates()
            ->when($q !== '', fn ($x) => $x->where(fn ($w) =>
                $w->where('prefix', 'like', "{$q}%")->orWhere('destination', 'like', "%{$q}%")))
            ->orderBy('prefix')
            ->paginate(50)->withQueryString();

        return view('ratecards.show', compact('ratecard', 'rates', 'q'));
    }

    public function edit(Ratecard $ratecard)
    {
        $this->authorize('update', $ratecard);
        return view('ratecards.form', ['ratecard' => $ratecard, 'mode' => 'edit', 'currencies' => $this->currencies()]);
    }

    /** Currency options for the form (id + name + symbol). */
    private function currencies()
    {
        return \Illuminate\Support\Facades\DB::connection('switch')->table('sys_currencies')->orderBy('name')->get();
    }

    public function update(Request $request, Ratecard $ratecard)
    {
        $this->authorize('update', $ratecard);
        // type is immutable after creation (its rates live in a type-specific table)
        $data = $this->validated($request, $ratecard);
        unset($data['ratecard_type']);
        $ratecard->fill($data);
        $ratecard->updated_by = $request->user()->email;
        $ratecard->updated_dt = now();
        $ratecard->save();

        return redirect()->route('ratecards.show', $ratecard)->with('status', 'Ratecard updated.');
    }

    private function validated(Request $request, ?Ratecard $ratecard = null): array
    {
        return $request->validate([
            'ratecard_name'        => ['required', 'string', 'max:30'],
            'ratecard_type'        => ['required', Rule::in(['CUSTOMER', 'CARRIER'])],
            'ratecard_for'         => ['required', Rule::in(['INCOMING', 'OUTGOING'])],
            'ratecard_currency_id' => ['required', 'integer', 'min:1'],
        ]);
    }

    private function uniqueKey(string $prefix): string
    {
        do {
            $key = $prefix . strtoupper(Str::random(6));
        } while (DB::connection('switch')->table('ratecard')->where('ratecard_id', $key)->exists());
        return $key;
    }
}
