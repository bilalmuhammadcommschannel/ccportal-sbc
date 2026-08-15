@extends('layouts.app')
@section('title','Change password')
@section('content')
    <h1>Change password</h1>
    @if(auth()->user()->must_change_password)
        <div class="errs">You must set a new password before using the portal.</div>
    @endif
    <div class="card" style="max-width:520px">
        <form method="POST" action="{{ route('password.update') }}">@csrf
            <div class="field"><label>Current password</label><input type="password" name="current_password" required autocomplete="current-password"></div>
            <div class="field"><label>New password</label><input type="password" name="password" required autocomplete="new-password"></div>
            <div class="field"><label>Confirm new password</label><input type="password" name="password_confirmation" required autocomplete="new-password"></div>
            <p class="muted">Minimum 12 characters with upper &amp; lower case, a number and a symbol. Checked against known breached passwords.</p>
            <button class="btn" type="submit">Update password</button>
        </form>
    </div>
@endsection
