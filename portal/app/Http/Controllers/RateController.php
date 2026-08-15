<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Ratecard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RateController extends Controller
{
    private const PLATFORM_ACCOUNT = 'SYSTEM';

    /** Store or update a single rate row (unique per ratecard_id + prefix). */
    public function store(Request $request, Ratecard $ratecard)
    {
        $this->authorize('update', $ratecard); // editing rates = updating the ratecard
        $data = $this->validateRate($request);
        $actor = $request->user()->email;

        $model = $ratecard->rateModelClass();
        $model::updateOrCreate(
            ['ratecard_id' => $ratecard->ratecard_id, 'prefix' => $data['prefix']],
            $this->rateAttributes($data, $ratecard, $actor)
        );

        return redirect()->route('ratecards.show', $ratecard)
            ->with('status', "Rate for prefix {$data['prefix']} saved.");
    }

    public function destroy(Request $request, Ratecard $ratecard, string $prefix)
    {
        $this->authorize('update', $ratecard);
        $model = $ratecard->rateModelClass();
        $model::where('ratecard_id', $ratecard->ratecard_id)->where('prefix', $prefix)->delete();

        return back()->with('status', "Rate for prefix {$prefix} removed.");
    }

    public function bulkForm(Ratecard $ratecard)
    {
        $this->authorize('update', $ratecard);
        return view('ratecards.bulk', compact('ratecard'));
    }

    /**
     * Bulk CSV import. Header row required. Columns:
     *   prefix,destination,rate[,minimal_time,resolution_time,setup_charge,
     *   connection_charge,inclusive_channel,exclusive_per_channel_rental,grace_period]
     * Required: prefix, destination, rate. Upsert on (ratecard_id, prefix).
     * Whole import runs in one transaction — all rows land or none do.
     */
    public function bulkImport(Request $request, Ratecard $ratecard)
    {
        $this->authorize('update', $ratecard);
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:8192'],
        ]);

        $actor = $request->user()->email;
        $model = $ratecard->rateModelClass();

        $fh = fopen($request->file('csv')->getRealPath(), 'r');
        if ($fh === false) {
            return back()->withErrors(['csv' => 'Could not read the uploaded file.']);
        }

        $header = fgetcsv($fh);
        if (! $header) {
            fclose($fh);
            return back()->withErrors(['csv' => 'Empty file.']);
        }
        $cols = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $need = ['prefix', 'destination', 'rate'];
        foreach ($need as $c) {
            if (! in_array($c, $cols, true)) {
                fclose($fh);
                return back()->withErrors(['csv' => "Missing required column: {$c}. Header must include prefix, destination, rate."]);
            }
        }

        $rows = [];
        $fails = [];
        $line = 1;
        $MAX = 20000;
        while (($rec = fgetcsv($fh)) !== false) {
            $line++;
            if ($line - 1 > $MAX) {
                $fails[] = "row {$line}: exceeds {$MAX}-row limit, not imported";
                continue;
            }
            $row = array_combine($cols, array_pad(array_slice($rec, 0, count($cols)), count($cols), null));
            $err = $this->validateCsvRow($row);
            if ($err) {
                $fails[] = "row {$line}: {$err}";
                continue;
            }
            $rows[] = $row;
        }
        fclose($fh);

        if (! $rows) {
            return back()->withErrors(['csv' => 'No valid rows.'])->with('import_fails', array_slice($fails, 0, 50));
        }

        $imported = 0;
        DB::connection('switch')->transaction(function () use ($rows, $model, $ratecard, $actor, &$imported) {
            foreach ($rows as $row) {
                $model::updateOrCreate(
                    ['ratecard_id' => $ratecard->ratecard_id, 'prefix' => trim((string) $row['prefix'])],
                    $this->rateAttributes($this->csvToData($row), $ratecard, $actor)
                );
                $imported++;
            }
        });

        return redirect()->route('ratecards.show', $ratecard)
            ->with('status', "Imported {$imported} rate(s)." . ($fails ? ' ' . count($fails) . ' row(s) skipped.' : ''))
            ->with('import_fails', array_slice($fails, 0, 50));
    }

    // ---- helpers -----------------------------------------------------------

    private function validateRate(Request $request): array
    {
        return $request->validate([
            'prefix'                       => ['required', 'string', 'max:25', 'regex:/^[0-9]+$/'],
            'destination'                  => ['required', 'string', 'max:150'],
            'rate'                         => ['required', 'numeric', 'min:0'],
            'setup_charge'                 => ['nullable', 'numeric', 'min:0'],
            'connection_charge'            => ['nullable', 'numeric', 'min:0'],
            'rental'                       => ['nullable', 'numeric', 'min:0'],
            'minimal_charge'               => ['nullable', 'numeric', 'min:0'],
            'minimal_time'                 => ['nullable', 'integer', 'min:0', 'max:86400'],
            'resolution_time'              => ['nullable', 'integer', 'min:1', 'max:86400'],
            'grace_period'                 => ['nullable', 'integer', 'min:0', 'max:86400'],
            'inclusive_channel'            => ['nullable', 'integer', 'min:0', 'max:100000'],
            'exclusive_per_channel_rental' => ['nullable', 'numeric', 'min:0'],
            'rate_multiplier'              => ['nullable', 'numeric', 'min:0'],
            'rate_addition'                => ['nullable', 'numeric'],
            'rates_status'                 => ['required', 'in:0,1'],
        ]);
    }

    private function validateCsvRow(array $row): ?string
    {
        if (! preg_match('/^[0-9]+$/', trim((string) ($row['prefix'] ?? '')))) {
            return 'prefix must be digits';
        }
        if (trim((string) ($row['destination'] ?? '')) === '') {
            return 'destination required';
        }
        if (! is_numeric($row['rate'] ?? null) || (float) $row['rate'] < 0) {
            return 'rate must be a non-negative number';
        }
        foreach (['minimal_time', 'resolution_time', 'grace_period', 'inclusive_channel'] as $intc) {
            if (isset($row[$intc]) && $row[$intc] !== '' && ! ctype_digit((string) $row[$intc])) {
                return "{$intc} must be a whole number";
            }
        }
        foreach (['setup_charge', 'connection_charge', 'exclusive_per_channel_rental'] as $numc) {
            if (isset($row[$numc]) && $row[$numc] !== '' && ! is_numeric($row[$numc])) {
                return "{$numc} must be numeric";
            }
        }
        return null;
    }

    private function csvToData(array $row): array
    {
        return [
            'prefix'                       => trim((string) $row['prefix']),
            'destination'                  => trim((string) $row['destination']),
            'rate'                         => $row['rate'],
            'minimal_time'                 => $row['minimal_time']    ?? null,
            'resolution_time'              => $row['resolution_time'] ?? null,
            'setup_charge'                 => $row['setup_charge']    ?? null,
            'connection_charge'            => $row['connection_charge'] ?? null,
            'inclusive_channel'            => $row['inclusive_channel'] ?? null,
            'exclusive_per_channel_rental' => $row['exclusive_per_channel_rental'] ?? null,
            'grace_period'                 => $row['grace_period'] ?? null,
            'rates_status'                 => '1',
        ];
    }

    /** Map validated/parsed input to the rate table's columns, filling defaults. */
    private function rateAttributes(array $d, Ratecard $ratecard, string $actor): array
    {
        return [
            'destination'                  => $d['destination'],
            'rate'                         => $d['rate'],
            'setup_charge'                 => $d['setup_charge'] ?? 0,
            'rental'                       => $d['rental'] ?? 0,
            'connection_charge'            => $d['connection_charge'] ?? 0,
            'minimal_time'                 => $d['minimal_time'] ?? 1,        // 1/1 default pulse
            'resolution_time'              => $d['resolution_time'] ?? 1,
            'grace_period'                 => $d['grace_period'] ?? 0,
            'rate_multiplier'              => $d['rate_multiplier'] ?? 1,
            'rate_addition'                => $d['rate_addition'] ?? 0,
            'rates_status'                 => $d['rates_status'] ?? '1',
            'exclusive_per_channel_rental' => $d['exclusive_per_channel_rental'] ?? 0,
            'inclusive_channel'            => $d['inclusive_channel'] ?? 1,
            'minimal_charge'               => $d['minimal_charge'] ?? null,
            'account_id'                   => $ratecard->account_id ?: self::PLATFORM_ACCOUNT,
            'updated_by'                   => $actor,
            'created_by'                   => $actor,
            'update_dt'                    => now(),
            'create_dt'                    => now(),
        ];
    }
}
