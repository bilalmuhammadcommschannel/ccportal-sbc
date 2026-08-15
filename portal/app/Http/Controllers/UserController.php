<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Portal operator/user management. Admin-only.
 *
 * Deliberate constraints, each mirroring an OV500 failure:
 *  - role is validated against a fixed set and a non-admin can never be granted
 *    'admin' here (OV500 let /profile mass-assign user_type → self-promotion);
 *  - a user cannot edit their own role or active flag (no self-escalation);
 *  - the last active admin cannot be demoted or disabled (no lock-out);
 *  - new users get a generated temporary password + must_change_password.
 */
class UserController extends Controller
{
    private const ROLES = ['admin', 'reseller', 'customer'];

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        $users = User::orderBy('email')->paginate(25);
        return view('users.index', compact('users'));
    }

    public function create(Request $request)
    {
        $this->authorizeAdmin($request);
        return view('users.form', ['mode' => 'create', 'u' => new User(['role' => 'customer', 'is_active' => true]), 'accounts' => $this->accounts()]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role'       => ['required', Rule::in(self::ROLES)],
            'account_id' => ['nullable', 'string', 'max:30', Rule::exists('switch.account', 'account_id')],
        ]);

        // a non-admin role must be scoped to an account; admin is platform-wide
        if ($data['role'] !== 'admin' && blank($data['account_id'])) {
            return back()->withErrors(['account_id' => 'Non-admin users must be tied to an account.'])->withInput();
        }

        $temp = Str::password(16);
        $user = new User($data);
        $user->account_id = $data['role'] === 'admin' ? null : $data['account_id'];
        $user->password = $temp;              // hashed by cast
        $user->must_change_password = true;   // forced rotation on first login
        $user->is_active = true;
        $user->save();

        Log::channel('auth')->info("portal user created {$user->email} role={$user->role} by {$request->user()->email}");

        return redirect()->route('users.index')
            ->with('status', "User {$user->email} created.")
            ->with('temp_password', $temp); // shown once, never stored in clear
    }

    public function edit(Request $request, User $user)
    {
        $this->authorizeAdmin($request);
        return view('users.form', ['mode' => 'edit', 'u' => $user, 'accounts' => $this->accounts()]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'role'       => ['required', Rule::in(self::ROLES)],
            'account_id' => ['nullable', 'string', 'max:30', Rule::exists('switch.account', 'account_id')],
            'is_active'  => ['required', 'in:0,1'],
        ]);

        $self = $request->user()->id === $user->id;
        if ($self && ($data['role'] !== $user->role || (string) $data['is_active'] !== (string) (int) $user->is_active)) {
            return back()->withErrors(['role' => 'You cannot change your own role or active state.'])->withInput();
        }

        // never allow removing the last active admin
        if ($user->role === 'admin' && ($data['role'] !== 'admin' || $data['is_active'] === '0')) {
            $others = User::where('role', 'admin')->where('is_active', true)->where('id', '!=', $user->id)->count();
            if ($others === 0) {
                return back()->withErrors(['role' => 'This is the last active admin — create another admin first.'])->withInput();
            }
        }

        if ($data['role'] !== 'admin' && blank($data['account_id'])) {
            return back()->withErrors(['account_id' => 'Non-admin users must be tied to an account.'])->withInput();
        }

        $user->fill([
            'name'      => $data['name'],
            'role'      => $data['role'],
            'is_active' => (bool) $data['is_active'],
        ]);
        $user->account_id = $data['role'] === 'admin' ? null : $data['account_id'];
        $user->save();

        Log::channel('auth')->info("portal user updated {$user->email} role={$user->role} active={$user->is_active} by {$request->user()->email}");

        return redirect()->route('users.index')->with('status', "User {$user->email} updated.");
    }

    /** Issue a new temporary password and force a change at next login. */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeAdmin($request);
        $temp = Str::password(16);
        $user->password = $temp;
        $user->must_change_password = true;
        $user->save();

        Log::channel('auth')->warning("portal password reset for {$user->email} by {$request->user()->email} from {$request->ip()}");

        return redirect()->route('users.index')
            ->with('status', "Temporary password issued for {$user->email}.")
            ->with('temp_password', $temp);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function accounts()
    {
        return Account::orderBy('account_id')->limit(500)->get();
    }
}
