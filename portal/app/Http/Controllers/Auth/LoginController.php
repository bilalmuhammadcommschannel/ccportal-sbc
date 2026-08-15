<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Per-identity + per-IP throttle. Replaces OV500's absent brute-force control.
        $key = strtolower($credentials['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            // fail2ban watches this line too — a client grinding through the throttle
            // should be banned at the firewall, not just told to wait.
            Log::channel('auth')->warning(
                "portal auth throttled for {$credentials['email']} from {$request->ip()}"
            );
            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 900); // 15-minute decay
            // Single-line, fixed format so fail2ban can regex the source IP.
            // Never log the password or whether the account exists.
            Log::channel('auth')->warning(
                "portal auth failure for {$credentials['email']} from {$request->ip()}"
            );
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.', // deliberately not which field
            ]);
        }

        Log::channel('auth')->info("portal auth success for {$credentials['email']} from {$request->ip()}");

        // A disabled account must not hold a session even with the right password.
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Log::channel('auth')->warning(
                "portal auth failure for {$credentials['email']} from {$request->ip()} (account disabled)"
            );
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate(); // defeat session fixation

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
