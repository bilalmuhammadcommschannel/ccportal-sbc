<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('account.password');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required', 'confirmed',
                Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            Log::channel('auth')->warning(
                "portal auth failure for {$user->email} from {$request->ip()}" // counts toward fail2ban
            );
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'New password must differ from the current one.']);
        }

        $user->password = $data['password']; // hashed by the model cast
        $user->must_change_password = false;
        $user->password_changed_at = now();
        $user->save();

        // Invalidate other sessions using the old credential.
        $request->session()->regenerate();
        Log::channel('auth')->info("portal password changed for {$user->email} from {$request->ip()}");

        return redirect()->route('dashboard')->with('status', 'Password updated.');
    }
}
