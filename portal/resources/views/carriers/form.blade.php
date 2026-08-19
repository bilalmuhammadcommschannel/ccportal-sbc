@extends('layouts.app')
@section('title', $mode === 'create' ? 'New carrier' : 'Edit carrier')
@section('content')
    @php
        $action = $mode === 'create' ? route('carriers.store') : route('carriers.update', $carrier);
        // rows to render: old input on validation failure, else the model's IPs, then pad with blanks
        $rows = old('ips');
        if (!$rows) {
            $rows = [];
            foreach ($ips as $ip) {
                $rows[] = [
                    'ipaddress_name' => $ip->ipaddress_name,
                    'ipaddress'      => $ip->ipaddress,
                    'auth_type'      => $ip->auth_type ?? 'IP',
                    'username'       => $ip->username,
                    'passwd'         => '', // never pre-fill secrets
                    'load_share'     => $ip->load_share ?? 1,
                    'priority'       => $ip->priority ?? 1,
                ];
            }
        }
        // always give one spare blank row to add another endpoint (no JS build in slice 1)
        $rows[] = ['ipaddress_name'=>'','ipaddress'=>'','auth_type'=>'IP','username'=>'','passwd'=>'','load_share'=>1,'priority'=>1];
    @endphp

    <div class="rowbar">
        <h1 style="margin:0">{{ $mode === 'create' ? 'New carrier' : 'Edit '.$carrier->carrier_name }}</h1>
        <a class="btn ghost sm" href="{{ route('carriers.index') }}">← Back</a>
    </div>

    <form method="POST" action="{{ $action }}">
        @csrf
        @if($mode === 'edit')@method('PUT')@endif

        <div class="card">
            <h2>Carrier</h2>
            <div class="grid">
                <div class="field">
                    <label>Name</label>
                    <input name="carrier_name" value="{{ old('carrier_name', $carrier->carrier_name) }}" required>
                </div>
                <div class="field">
                    <label>Carrier tariff</label>
                    <select name="tariff_id" required>
                        <option value="">— select —</option>
                        @foreach($tariffs as $tid => $tname)
                            <option value="{{ $tid }}" @selected(old('tariff_id', $carrier->tariff_id) === $tid)>{{ $tname }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid3">
                <div class="field">
                    <label>Direction</label>
                    <select name="carrier_type">
                        @foreach(['OUTBOUND','INBOUND'] as $t)
                            <option value="{{ $t }}" @selected(old('carrier_type', $carrier->carrier_type) === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="carrier_status">
                        <option value="1" @selected((string)old('carrier_status', $carrier->carrier_status) === '1')>Active</option>
                        <option value="0" @selected((string)old('carrier_status', $carrier->carrier_status) === '0')>Inactive</option>
                    </select>
                </div>
                <div class="field">
                    <label>CLI prefer</label>
                    <select name="cli_prefer">
                        @foreach(['rpid','pid','no'] as $t)
                            <option value="{{ $t }}" @selected(old('cli_prefer', $carrier->cli_prefer ?? 'rpid') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid3">
                <div class="field"><label>Max CPS</label><input type="number" name="carrier_cps" min="0" value="{{ old('carrier_cps', $carrier->carrier_cps ?? 0) }}"></div>
                <div class="field"><label>Max channels (CC)</label><input type="number" name="carrier_cc" min="0" value="{{ old('carrier_cc', $carrier->carrier_cc ?? 0) }}"></div>
                <div class="field">
                    <label>Tax type</label>
                    <select name="tax_type">
                        @foreach(['exclusive','inclusive'] as $t)
                            <option value="{{ $t }}" @selected(old('tax_type', $carrier->tax_type ?? 'exclusive') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="field"><label>Codecs</label><input name="carrier_codecs" value="{{ old('carrier_codecs', $carrier->carrier_codecs ?? 'PCMU,PCMA') }}"></div>
        </div>

        <div class="card">
            <h2>Outbound dialing (digit manipulation)</h2>
            <p class="muted" style="margin-top:-6px">Applied to the dialled number before it is sent to this carrier: first <strong>strip</strong> a leading prefix, then <strong>prepend</strong>. Example: prepend <code>+</code> so numbers reach Twilio as <code>+E.164</code>. Leave blank for no change.</p>
            <div class="grid">
                <div class="field"><label>Strip leading (digits)</label><input name="out_strip" value="{{ old('out_strip', $strip ?? '') }}" placeholder="e.g. 0"></div>
                <div class="field"><label>Prepend</label><input name="out_prepend" value="{{ old('out_prepend', $prepend ?? '') }}" placeholder="e.g. + or 61"></div>
            </div>
        </div>

        <div class="card">
            <h2>Signalling endpoints</h2>
            <p class="muted" style="margin-top:-6px">Auth type <strong>IP</strong> = the carrier trusts our source IP. <strong>CUSTOMER</strong> = SIP credential auth; username &amp; password required. Leave the last (blank) row empty if not needed.</p>
            @foreach($rows as $i => $r)
                <div class="ipset">
                    <div class="grid3">
                        <div class="field"><label>Endpoint name</label><input name="ips[{{ $i }}][ipaddress_name]" value="{{ $r['ipaddress_name'] }}"></div>
                        <div class="field"><label>IP address</label><input name="ips[{{ $i }}][ipaddress]" value="{{ $r['ipaddress'] }}" placeholder="e.g. 203.0.113.10"></div>
                        <div class="field">
                            <label>Auth type</label>
                            <select name="ips[{{ $i }}][auth_type]">
                                <option value="IP" @selected(($r['auth_type'] ?? 'IP') === 'IP')>IP</option>
                                <option value="CUSTOMER" @selected(($r['auth_type'] ?? '') === 'CUSTOMER')>CUSTOMER (credentials)</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid3">
                        <div class="field"><label>Username <span class="muted">(credential auth)</span></label><input name="ips[{{ $i }}][username]" value="{{ $r['username'] }}"></div>
                        <div class="field"><label>Password <span class="muted">(leave blank to keep)</span></label><input type="password" name="ips[{{ $i }}][passwd]" value=""></div>
                        <div class="field"><label>Weight / priority</label>
                            <div style="display:flex;gap:8px">
                                <input type="number" name="ips[{{ $i }}][load_share]" value="{{ $r['load_share'] ?? 1 }}" title="load share">
                                <input type="number" name="ips[{{ $i }}][priority]" value="{{ $r['priority'] ?? 1 }}" title="priority">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button class="btn" type="submit">{{ $mode === 'create' ? 'Create carrier' : 'Save changes' }}</button>
    </form>
@endsection
