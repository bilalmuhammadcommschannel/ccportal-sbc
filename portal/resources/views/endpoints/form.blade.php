@extends('layouts.app')
@section('title', $mode==='create'?'New endpoint':'Edit endpoint')
@section('content')
    @php $a=$mode==='create'?route('endpoints.store'):route('endpoints.update',$endpoint); @endphp
    <div class="rowbar"><h1 style="margin:0">{{ $mode==='create'?'New endpoint':'Edit '.$endpoint->username }}</h1><a class="btn ghost sm" href="{{ route('endpoints.index') }}">← Back</a></div>
    <form method="POST" action="{{ $a }}">@csrf @if($mode==='edit')@method('PUT')@endif
        <div class="card"><h2>Identity & auth</h2>
            <div class="grid3">
                <div class="field"><label>SIP username</label><input name="username" value="{{ old('username',$endpoint->username) }}" {{ $mode==='edit'?'readonly':'' }} required></div>
                <div class="field"><label>Password @if($mode==='edit')<span class="muted">(blank = keep)</span>@endif</label><input type="password" name="secret" value="" {{ $mode==='create'?'required':'' }}></div>
                <div class="field"><label>Account</label><select name="account_id" required><option value="">— select —</option>@foreach($accounts as $ac)<option value="{{ $ac->account_id }}" @selected(old('account_id',$endpoint->account_id)===$ac->account_id)>{{ $ac->account_id }} ({{ $ac->account_type }})</option>@endforeach</select></div>
            </div>
            <div class="grid3">
                <div class="field"><label>Auth mode</label><select name="ipauthfrom">@foreach(['NO'=>'Password','SRC'=>'IP (source)','FROM'=>'IP (From)'] as $v=>$l)<option value="{{ $v }}" @selected(old('ipauthfrom',$endpoint->ipauthfrom)===$v)>{{ $l }}</option>@endforeach</select></div>
                <div class="field"><label>IP address (for IP auth)</label><input name="ipaddress" value="{{ old('ipaddress',$endpoint->ipaddress) }}" placeholder="optional"></div>
                <div class="field"><label>Status</label><select name="status"><option value="1" @selected((string)old('status',$endpoint->status)==='1')>Active</option><option value="0" @selected((string)old('status',$endpoint->status)==='0')>Inactive</option></select></div>
            </div>
        </div>
        <div class="card"><h2>Capacity & media</h2>
            <div class="grid3">
                <div class="field"><label>Max channels</label><input type="number" name="sip_cc" value="{{ old('sip_cc',$endpoint->sip_cc ?? 1) }}" min="1"></div>
                <div class="field"><label>Max CPS</label><input type="number" name="sip_cps" value="{{ old('sip_cps',$endpoint->sip_cps ?? 1) }}" min="1"></div>
                <div class="field"><label>Codecs</label><input name="codecs" value="{{ old('codecs',$endpoint->codecs ?? 'G729,PCMU,PCMA') }}"></div>
            </div>
            <div class="grid3">
                <div class="field"><label>Caller ID</label><input name="caller_id" value="{{ old('caller_id',$endpoint->caller_id) }}"></div>
                <div class="field"><label>CLI prefer</label><select name="cli_prefer">@foreach(['rpid','pid','no'] as $c)<option value="{{ $c }}" @selected(old('cli_prefer',$endpoint->cli_prefer)===$c)>{{ $c }}</option>@endforeach</select></div>
                <div class="field"><label>Display name</label><input name="display_name" value="{{ old('display_name',$endpoint->display_name) }}"></div>
            </div>
            <div class="grid3">
                <div class="field"><label>Call recording</label><select name="call_recording"><option value="0" @selected((string)old('call_recording',$endpoint->call_recording ?? '0')==='0')>Off</option><option value="1" @selected((string)old('call_recording',$endpoint->call_recording)==='1')>On</option></select></div>
                <div class="field"><label>DND</label><select name="dnd"><option value="N" @selected(old('dnd',$endpoint->dnd ?? 'N')==='N')>No</option><option value="Y" @selected(old('dnd',$endpoint->dnd)==='Y')>Yes</option></select></div>
            </div>
        </div>
        <div class="card"><h2>Contact (required by schema)</h2>
            <div class="grid3">
                <div class="field"><label>Name</label><input name="name" value="{{ old('name',$endpoint->name) }}" required></div>
                <div class="field"><label>Email</label><input type="email" name="email_address" value="{{ old('email_address',$endpoint->email_address) }}" required></div>
                <div class="field"><label>Phone</label><input name="phone_number" value="{{ old('phone_number',$endpoint->phone_number) }}" required></div>
            </div>
        </div>
        <div class="card"><h2>Custom SIP headers</h2>
            <p class="muted" style="margin-top:-6px">Injected on calls for this endpoint (the switch emits <code>sip_h_&lt;name&gt;</code>). e.g. <code>X-Account</code>, <code>P-Charge-Info</code>. Leave the spare row blank if not needed.</p>
            @php
                $hrows = old('headers');
                if (!$hrows) { $hrows = []; foreach (($endpoint->headers ?? []) as $h) { $hrows[] = ['name'=>$h->header_name,'value'=>$h->header_value,'direction'=>$h->direction]; } }
                $hrows[] = ['name'=>'','value'=>'','direction'=>'outbound'];
            @endphp
            @foreach($hrows as $i => $h)
                <div class="grid3">
                    <div class="field"><label>Header name</label><input name="headers[{{ $i }}][name]" value="{{ $h['name'] }}" placeholder="X-Custom-Header"></div>
                    <div class="field"><label>Value</label><input name="headers[{{ $i }}][value]" value="{{ $h['value'] }}"></div>
                    <div class="field"><label>Direction</label><select name="headers[{{ $i }}][direction]">@foreach(['outbound','inbound','both'] as $d)<option value="{{ $d }}" @selected(($h['direction'] ?? 'outbound')===$d)>{{ $d }}</option>@endforeach</select></div>
                </div>
            @endforeach
        </div>
        <button class="btn" type="submit">{{ $mode==='create'?'Create endpoint':'Save changes' }}</button>
    </form>
@endsection
