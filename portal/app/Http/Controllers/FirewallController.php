<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\Process\Process;

/**
 * Firewall — banned-IP visibility + unblock. Admin only (route-gated).
 *
 * The web process has no fail2ban privileges: it READS the ban list from the
 * root-written snapshot (/var/lib/ccportal/stats.json, key fail2ban.bans) and
 * UNBANS through one narrowly-scoped setuid-style helper invoked via sudo:
 *   sudo /usr/local/sbin/cc-fw unban <jail> <ip>
 * The helper re-validates both arguments against a hard allowlist, so even a
 * bug here cannot run arbitrary commands as root.
 */
class FirewallController extends Controller
{
    private const STATS_FILE = '/var/lib/ccportal/stats.json';

    /** Jails the helper (and this controller) will act on. Keep in sync with cc-fw. */
    private const JAILS = ['ccportal-auth', 'ccportal-probe', 'ccsip-auth', 'ccsip-scanner', 'sshd'];

    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $stats = $this->stats();
        $bans = collect(data_get($stats, 'fail2ban.bans', []))
            ->map(fn ($b) => [
                'ip'    => (string) ($b['ip'] ?? ''),
                'jail'  => (string) ($b['jail'] ?? ''),
                'since' => (string) ($b['since'] ?? ''),
                'until' => (string) ($b['until'] ?? ''),
            ])
            ->filter(fn ($b) => $b['ip'] !== '')
            ->sortByDesc('since')
            ->values();

        // jail -> currently-banned count, for the filter chips + header
        $jailCounts = collect(data_get($stats, 'fail2ban.jails', []))
            ->mapWithKeys(fn ($j) => [$j['name'] => (int) ($j['currently_banned'] ?? 0)]);

        return view('firewall.index', [
            'bans'       => $bans,
            'jailCounts' => $jailCounts,
            'totalBanned' => (int) data_get($stats, 'fail2ban.currently_banned', 0),
            'totalFailed' => (int) data_get($stats, 'fail2ban.total_failed', 0),
            'stale'      => $stats['stale'] ?? false,
            'age'        => $stats['age_sec'] ?? null,
        ]);
    }

    public function unban(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            // Accept IPv4 or IPv6; the helper validates again before touching fail2ban.
            'ip'   => ['required', 'string', 'max:45', function ($attr, $value, $fail) {
                if (filter_var($value, FILTER_VALIDATE_IP) === false) {
                    $fail('Not a valid IP address.');
                }
            }],
            'jail' => ['required', Rule::in(self::JAILS)],
        ]);

        $proc = new Process(['sudo', '-n', '/usr/local/sbin/cc-fw', 'unban', $data['jail'], $data['ip']]);
        $proc->setTimeout(15);
        $proc->run();

        if ($proc->isSuccessful()) {
            Log::info("firewall unban {$data['ip']} from {$data['jail']} by {$request->user()->email}");
            return back()->with('status', "Unblocked {$data['ip']} ({$data['jail']}). The list refreshes within ~20s.");
        }

        Log::warning("firewall unban FAILED {$data['ip']} / {$data['jail']}: " . trim($proc->getErrorOutput()));
        return back()->with('fw_error', "Could not unblock {$data['ip']} — {$this->reason($proc->getExitCode())}");
    }

    private function reason(?int $code): string
    {
        return match ($code) {
            2, 3, 4 => 'the helper rejected the request (invalid jail or IP).',
            default => 'the unblock command failed on the server. Check cc-fw / sudoers.',
        };
    }

    private function stats(): array
    {
        if (! is_readable(self::STATS_FILE)) {
            return ['stale' => true];
        }
        $data = json_decode((string) @file_get_contents(self::STATS_FILE), true) ?: [];
        $data['stale'] = (time() - (int) @filemtime(self::STATS_FILE)) > 120;
        $data['age_sec'] = time() - (int) @filemtime(self::STATS_FILE);
        return $data;
    }
}
