@extends('layouts.app')
@section('title', $mode==='create'?'New user':'Edit user')
@section('content')
    @php $a = $mode==='create' ? route('users.store') : route('users.update',$u); @endphp
    <div class="rowbar"><h1 style="margin:0">{{ $mode==='create'?'New portal user':'Edit '.$u->email }}</h1><a class="btn ghost sm" href="{{ route('users.index') }}">← Back</a></div>
    <form method="POST" action="{{ $a }}">@csrf @if($mode==='edit')@method('PUT')@endif
        <div class="card">
            <div class="grid">
                <div class="field"><label>Name</label><input name="name" value="{{ old('name',$u->name) }}" required></div>
                <div class="field"><label>Email</label>
                    @if($mode==='create')<input type="email" name="email" value="{{ old('email') }}" required>
                    @else<input value="{{ $u->email }}" disabled>@endif
                </div>
            </div>
            <div class="grid">
                <div class="field"><label>Role</label>
                    <select name="role">@foreach(['admin','reseller','customer'] as $r)<option value="{{ $r }}" @selected(old('role',$u->role)===$r)>{{ ucfirst($r) }}</option>@endforeach</select>
                    <span class="muted" style="font-size:12px">Admin is platform-wide; reseller/customer must be tied to an account.</span>
                </div>
                <div class="field"><label>Account (non-admin)</label>
                    <select name="account_id"><option value="">— none (admin) —</option>
                        @foreach($accounts as $ac)<option value="{{ $ac->account_id }}" @selected(old('account_id',$u->account_id)===$ac->account_id)>{{ $ac->account_id }} ({{ $ac->account_type }})</option>@endforeach
                    </select>
                </div>
            </div>
            @if($mode==='edit')
            <div class="field" style="max-width:220px"><label>Status</label>
                <select name="is_active"><option value="1" @selected((string)old('is_active',(int)$u->is_active)==='1')>Active</option><option value="0" @selected((string)old('is_active',(int)$u->is_active)==='0')>Disabled</option></select>
            </div>
            @else
            <p class="muted">A temporary password will be generated and shown once. The user must change it at first login.</p>
            @endif
        </div>
        <button class="btn" type="submit">{{ $mode==='create'?'Create user':'Save changes' }}</button>
    </form>
@endsection
