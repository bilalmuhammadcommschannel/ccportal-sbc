<?php

namespace App\Http\Controllers;

use App\Models\Ov500\RatedCdr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Operations dashboard: live calls, today's traffic/revenue/profit, peak-call
 * load, system health, and (admin only) platform + security posture.
 *
 * Security/health stats come from /var/lib/ccportal/stats.json, written by a
 * root systemd timer (cc-stats.timer). The web process deliberately has no
 * fail2ban/fs_cli privileges — it only reads that snapshot (and a rolling
 * history file for the CPU/RAM charts).
 */
class DashboardController extends Controller
{
    private const STATS_FILE = '/var/lib/ccportal/stats.json';
    private const HIST_FILE  = '/var/lib/ccportal/stats-history.json';

    /** Business timezone: figures ("today", peak hours) are in local AU time. */
    private const TZ = 'Australia/Sydney';

    /**
     * Gross margin baked into customer ratecards (customer rate = carrier rate
     * x 1.30). Per-CDR carrier cost is not stored, so profit is derived from the
     * charged revenue: profit = revenue - revenue/1.30 = revenue * (0.30/1.30).
     */
    private const MARGIN = 0.30 / 1.30;

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();
        $scope = $isAdmin ? null : $user->accessibleAccountIds();
        // Apply tenancy scope to any builder with an `account_id` column, returning it.
        $scoped = function ($q) use ($scope) {
            if ($scope !== null) {
                $q->whereIn('account_id', $scope ?: ['__none__']);
            }
            return $q;
        };

        $now       = Carbon::now(self::TZ);
        $dayStart  = $now->copy()->startOfDay()->utc();
        $monStart  = $now->copy()->startOfMonth()->utc();
        $nowUtc    = Carbon::now();

        // --- today's traffic (tenancy-scoped, AU day) ---
        $today = $scoped(RatedCdr::query())->whereBetween('rated_at', [$dayStart, $nowUtc]);
        $traffic = [
            'calls'   => (clone $today)->count(),
            'minutes' => round(((int) (clone $today)->sum('billed_seconds')) / 60, 1),
            'cost'    => (string) ((clone $today)->sum('cost') ?: '0'),
        ];

        // --- revenue + derived profit (today headline, month-to-date sub) ---
        $revToday = (float) (clone $today)->sum('cost');
        $revMonth = (float) $scoped(RatedCdr::query())->whereBetween('rated_at', [$monStart, $nowUtc])->sum('cost');
        $profit = [
            'rev_today'  => $revToday,
            'rev_month'  => $revMonth,
            'today'      => round($revToday * self::MARGIN, 2),
            'month'      => round($revMonth * self::MARGIN, 2),
            'margin_pct' => 30,
        ];

        // --- peak calls by hour of day (AU local), for the load chart ---
        $offMin = (int) $now->utcOffset(); // e.g. 600 for AEST
        $byHour = $scoped(RatedCdr::query())
            ->whereBetween('rated_at', [$dayStart, $nowUtc])
            ->selectRaw("HOUR(DATE_ADD(rated_at, INTERVAL {$offMin} MINUTE)) AS h, COUNT(*) AS c")
            ->groupBy('h')->pluck('c', 'h');
        $callHours = [];
        for ($h = 0; $h < 24; $h++) {
            $callHours[$h] = (int) ($byHour[$h] ?? 0);
        }

        // --- counts the operator cares about ---
        $counts = [
            'customers' => $scoped(DB::connection('switch')->table('account')->where('account_type', 'CUSTOMER'))->count(),
            'resellers' => $scoped(DB::connection('switch')->table('account')->where('account_type', 'RESELLER'))->count(),
            'endpoints' => $scoped(DB::connection('switch')->table('customer_sip_account'))->count(),
            'dids'      => $scoped(DB::connection('switch')->table('did'))->count(),
            'carriers'  => $isAdmin ? DB::connection('switch')->table('carrier')->count() : null,
        ];

        // --- prepaid accounts running low (actionable, tenancy-scoped) ---
        $lowBalance = DB::connection('switch')->table('customer_balance as b')
            ->leftJoin('customers as c', 'c.account_id', '=', 'b.account_id')
            ->when($scope !== null, fn ($q) => $q->whereIn('b.account_id', $scope ?: ['__none__']))
            ->where('c.billing_type', 'prepaid')
            ->where('b.balance', '<', 5)
            ->orderBy('b.balance')
            ->limit(8)
            ->get(['b.account_id', 'b.balance', 'c.company_name']);

        // --- live calls + platform/security snapshot + health history ---
        $stats = $this->stats();
        $history = $this->history();

        return view('dashboard', compact(
            'traffic', 'profit', 'callHours', 'counts', 'lowBalance', 'stats', 'history', 'isAdmin'
        ));
    }

    /** JSON endpoint the dashboard polls so the page refreshes without a reload. */
    public function live(Request $request)
    {
        $s = $this->stats();
        $isAdmin = $request->user()->isAdmin();
        return response()->json([
            'generated_at' => $s['generated_at'] ?? null,
            'calls'        => data_get($s, 'freeswitch.calls', 0),
            'channels'     => data_get($s, 'freeswitch.channels', 0),
            // channel detail can name other tenants' calls — admin only
            'channel_list' => $isAdmin ? data_get($s, 'freeswitch.channel_list', []) : [],
            // security posture + host metrics are admin-only (match the HTML view)
            'banned'       => $isAdmin ? data_get($s, 'fail2ban.currently_banned', 0) : null,
            'attacks'      => $isAdmin ? data_get($s, 'fail2ban.total_failed', 0) : null,
            'cpu'          => $isAdmin ? data_get($s, 'host.cpu_pct', 0) : null,
            'mem'          => $isAdmin ? data_get($s, 'host.mem_pct', 0) : null,
            'stale'        => $s['stale'] ?? false,
            'admin'        => $request->user()->isAdmin(),
        ]);
    }

    private function stats(): array
    {
        if (! is_readable(self::STATS_FILE)) {
            return ['stale' => true];
        }
        $data = json_decode((string) @file_get_contents(self::STATS_FILE), true) ?: [];
        $data['stale'] = (time() - (int) @filemtime(self::STATS_FILE)) > 120;
        $data['age_sec'] = time() - (int) @filemtime(self::STATS_FILE);
        return $this->enrichChannels($data);
    }

    /**
     * Turn raw live-channel data into operator-facing rows: resolve the account
     * id to a company name and the carrier id to a carrier name, and decode the
     * dialled/caller numbers. FreeSWITCH internals (channel names) are dropped.
     */
    private function enrichChannels(array $data): array
    {
        $list = data_get($data, 'freeswitch.channel_list', []);
        if (empty($list)) {
            return $data;
        }
        $sw = DB::connection('switch');
        $acctIds = collect($list)->pluck('account')->filter()->unique()->values();
        $carrIds = collect($list)->pluck('carrier_id')->filter()->unique()->values();
        $companies = $acctIds->isEmpty() ? collect()
            : $sw->table('customers')->whereIn('account_id', $acctIds)->pluck('company_name', 'account_id');
        $carriers = $carrIds->isEmpty() ? collect()
            : $sw->table('carrier')->whereIn('carrier_id', $carrIds)->pluck('carrier_name', 'carrier_id');

        foreach ($list as &$c) {
            $acct = $c['account'] ?? '';
            $cid  = $c['carrier_id'] ?? '';
            $c['account_label'] = ($acct !== '' ? ($companies[$acct] ?? $acct) : '—');
            $c['trunk_label']   = $cid !== '' && isset($carriers[$cid]) ? $carriers[$cid]
                                    : (($c['trunk'] ?? '') === 'endpoint' || ($c['trunk'] ?? '') === '' ? 'Endpoint' : $c['trunk']);
            $c['cid']  = rawurldecode((string) ($c['cid'] ?? ''));
            $c['dest'] = rawurldecode((string) ($c['dest'] ?? ''));
        }
        unset($c);
        $data['freeswitch']['channel_list'] = $list;
        return $data;
    }

    /** Rolling CPU/RAM/live-call samples for the health sparklines (may be absent). */
    private function history(): array
    {
        if (! is_readable(self::HIST_FILE)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents(self::HIST_FILE), true) ?: [];
        return array_slice($data['samples'] ?? [], -90); // last ~30 min @ 20s
    }
}
