<?php

namespace App\Http\Controllers;

use App\Models\Ov500\RatedCdr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Operations dashboard: live calls, today's traffic/revenue, and (admin only)
 * platform + security posture.
 *
 * Security stats come from /var/lib/ccportal/stats.json, written by a root
 * systemd timer (cc-stats.timer). The web process deliberately has no
 * fail2ban/fs_cli privileges — it only reads that snapshot.
 */
class DashboardController extends Controller
{
    private const STATS_FILE = '/var/lib/ccportal/stats.json';

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();
        $scope = $isAdmin ? null : $user->accessibleAccountIds();

        // --- today's traffic (tenancy-scoped) ---
        $today = RatedCdr::query()
            ->when($scope !== null, fn ($q) => $q->whereIn('account_id', $scope ?: ['__none__']))
            ->whereDate('rated_at', now()->toDateString());

        $traffic = [
            'calls'   => (clone $today)->count(),
            'minutes' => round(((int) (clone $today)->sum('billed_seconds')) / 60, 1),
            'cost'    => (string) ((clone $today)->sum('cost') ?: '0'),
        ];

        // --- counts the operator cares about ---
        $counts = [
            'customers' => DB::connection('switch')->table('account')->where('account_type', 'CUSTOMER')
                ->when($scope !== null, fn ($q) => $q->whereIn('account_id', $scope ?: ['__none__']))->count(),
            'resellers' => DB::connection('switch')->table('account')->where('account_type', 'RESELLER')
                ->when($scope !== null, fn ($q) => $q->whereIn('account_id', $scope ?: ['__none__']))->count(),
            'endpoints' => DB::connection('switch')->table('customer_sip_account')
                ->when($scope !== null, fn ($q) => $q->whereIn('account_id', $scope ?: ['__none__']))->count(),
            'dids'      => DB::connection('switch')->table('did')
                ->when($scope !== null, fn ($q) => $q->whereIn('account_id', $scope ?: ['__none__']))->count(),
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

        // --- live calls + platform/security snapshot ---
        $stats = $this->stats();

        return view('dashboard', compact('traffic', 'counts', 'lowBalance', 'stats', 'isAdmin'));
    }

    /** JSON endpoint the dashboard polls so the page refreshes without a reload. */
    public function live(Request $request)
    {
        $s = $this->stats();
        return response()->json([
            'generated_at' => $s['generated_at'] ?? null,
            'calls'        => data_get($s, 'freeswitch.calls', 0),
            'channels'     => data_get($s, 'freeswitch.channels', 0),
            'channel_list' => data_get($s, 'freeswitch.channel_list', []),
            'banned'       => data_get($s, 'fail2ban.currently_banned', 0),
            'attacks'      => data_get($s, 'fail2ban.total_failed', 0),
            'admin'        => $request->user()->isAdmin(),
        ]);
    }

    private function stats(): array
    {
        if (! is_readable(self::STATS_FILE)) {
            return ['stale' => true];
        }
        $raw = @file_get_contents(self::STATS_FILE);
        $data = json_decode((string) $raw, true) ?: [];
        // flag a snapshot the collector has stopped refreshing
        $data['stale'] = (time() - (int) @filemtime(self::STATS_FILE)) > 120;
        $data['age_sec'] = time() - (int) @filemtime(self::STATS_FILE);
        return $data;
    }
}
