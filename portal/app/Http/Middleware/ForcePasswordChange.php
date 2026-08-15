<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A user flagged must_change_password can reach nothing except the change-password
 * screen (and logout). Stops an operator-issued temporary credential from being
 * used indefinitely.
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->must_change_password) {
            $allowed = ['password.edit', 'password.update', 'logout'];
            if (! in_array($request->route()?->getName(), $allowed, true)) {
                return redirect()->route('password.edit')
                    ->with('status', 'Please set a new password before continuing.');
            }
        }
        return $next($request);
    }
}
