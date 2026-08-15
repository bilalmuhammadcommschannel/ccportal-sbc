<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every state-changing request (money, authz, provisioning) so an incident
 * can be reconstructed: who, from where, what path, which subject, what changed.
 * The OV500 audit hook logged only REQUEST_URI, so a balance credit or tariff
 * rewrite could not be attributed — this captures the payload too, with secrets
 * stripped.
 */
class AuditTrail
{
    private const SENSITIVE = ['password', 'password_confirmation', 'secret', 'passwd', '_token', 'current_password'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            try {
                $payload = collect($request->except(self::SENSITIVE))
                    ->map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v))
                    ->take(40)->all();

                DB::table('cc_audit_log')->insert([
                    'user_email' => $request->user()?->email,
                    'role'       => $request->user()?->role,
                    'ip'         => $request->ip(),
                    'method'     => $request->method(),
                    'path'       => substr($request->path(), 0, 255),
                    'action'     => substr((string) $request->route()?->getName(), 0, 64),
                    'subject'    => substr(collect($request->route()?->parameters() ?? [])
                                      ->map(fn ($p) => is_object($p) ? ($p->getKey() ?? '') : (string) $p)
                                      ->implode(','), 0, 128),
                    'payload'    => substr(json_encode($payload), 0, 4000),
                    'status'     => $response->getStatusCode(),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // auditing must never break the request
                report($e);
            }
        }

        return $response;
    }
}
