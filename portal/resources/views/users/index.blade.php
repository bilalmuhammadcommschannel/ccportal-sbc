@extends('layouts.app')
@section('title','Portal users')
@section('content')
    <div class="rowbar"><h1 style="margin:0">Portal users</h1><a class="btn" href="{{ route('users.create') }}">+ New user</a></div>

    @if(session('temp_password'))
        <div class="flash"><strong>Temporary password (shown once):</strong>
            <code style="font-size:15px">{{ session('temp_password') }}</code><br>
            <span class="muted">Give this to the user over a secure channel. They must change it at first login.</span>
        </div>
    @endif

    <div class="card">
        <table>
            <thead><tr><th>Email</th><th>Name</th><th>Role</th><th>Account</th><th>Status</th><th>Last login</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->name }}</td>
                    <td>{{ ucfirst($u->role) }}</td>
                    <td class="muted">{{ $u->account_id ?? 'platform' }}</td>
                    <td>
                        @if(!$u->is_active)<span class="pill off">Disabled</span>
                        @elseif($u->must_change_password)<span class="pill off">Must change pw</span>
                        @else<span class="pill on">Active</span>@endif
                    </td>
                    <td class="muted">{{ $u->last_login_at ? $u->last_login_at.' · '.$u->last_login_ip : 'never' }}</td>
                    <td class="right">
                        <a class="btn ghost sm" href="{{ route('users.edit',$u) }}">Edit</a>
                        <form method="POST" action="{{ route('users.resetPassword',$u) }}" style="display:inline" onsubmit="return confirm('Issue a new temporary password for {{ $u->email }}?')">@csrf<button class="btn ghost sm">Reset pw</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $users->links() }}</div>
    </div>
@endsection
