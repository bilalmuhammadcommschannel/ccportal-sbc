<?php

use App\Http\Middleware\AuditTrail;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\SwitchAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // FreeSWITCH-facing endpoints — no session, no CSRF (machine-to-machine);
            // guarded by SwitchAuth (loopback + shared secret).
            Route::middleware(SwitchAuth::class)->group(base_path('routes/switch.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // Audit every state change, and force rotation of temporary passwords.
        // Appended to the web group so they run after auth/session are available.
        $middleware->web(append: [
            ForcePasswordChange::class,
            AuditTrail::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
