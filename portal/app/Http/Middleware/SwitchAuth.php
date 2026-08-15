<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the FreeSWITCH-facing endpoints. Defence in depth:
 *   1. source must be loopback (FreeSWITCH runs on this host; nginx also binds
 *      the switch listener to 127.0.0.1);
 *   2. a shared secret (SWITCH_SHARED_SECRET) must match, compared with
 *      hash_equals (constant-time).
 * These endpoints carry NO session and NO CSRF — they are machine-to-machine.
 * This is the inverse of OV500's api/, which had no auth and was internet-exposed.
 */
class SwitchAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        if (! in_array($ip, ['127.0.0.1', '::1'], true)) {
            abort(403, 'switch endpoints are loopback-only');
        }

        $expected = (string) config('switch.secret');
        $given = (string) ($request->header('X-CC-Switch-Secret') ?? $request->input('secret', ''));
        if ($expected === '' || ! hash_equals($expected, $given)) {
            abort(403, 'bad switch secret');
        }

        return $next($request);
    }
}
