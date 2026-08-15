<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level role gate. Default-deny: the route must name the roles allowed,
 * and a user whose role is not in that set gets a 403. Applied in addition to
 * per-object policies, not instead of them.
 *
 *   Route::...->middleware('role:admin')
 *   Route::...->middleware('role:admin,reseller')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
