@extends('layouts.app')
@section('title','Assign DID '.$did->did_number)
@section('content')
    <div class="rowbar"><h1 style="margin:0">Assign / edit {{ $did->did_number }}</h1><a class="btn ghost sm" href="{{ route('dids.show',$did) }}">← Back</a></div>
    <form method="POST" action="{{ route('dids.update',$did) }}">@csrf @method('PUT')
        <div class="card"><h2>Assignment</h2>
            <div class="grid">
                <div class="field"><label>Assigned account</label>
                    {{-- searchable combobox: shows the company name, filters as you type; the
                         hidden #acctSel carries the submitted account_id (kept for the routing JS). --}}
                    <input type="hidden" name="account_id" id="acctSel" value="{{ old('account_id',$did->account_id) }}">
                    <div class="combo">
                        <input type="text" id="acctSearch" class="combo-input" autocomplete="off" placeholder="Search customer name or account ID…">
                        <div id="acctList" class="combo-list" hidden></div>
                    </div>
                </div>
                <div class="field"><label>Status</label><select name="did_status">@foreach(['NEW','USED','DEAD','BLOCKED'] as $s)<option value="{{ $s }}" @selected(old('did_status',$did->did_status)===$s)>{{ $s }}</option>@endforeach</select></div>
            </div>
            <div class="grid">
                <div class="field"><label>Channels</label><input type="number" name="channels" value="{{ old('channels',$did->channels) }}" min="1"></div>
                <div class="field"><label>Provider carrier <span class="muted">(who delivers inbound)</span></label>
                    <select name="carrier_id"><option value="">— none —</option>
                        @foreach($carriers as $cr)<option value="{{ $cr->carrier_id }}" @selected(old('carrier_id',$did->carrier_id)===$cr->carrier_id)>{{ $cr->carrier_name }} ({{ $cr->carrier_type }})</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="grid">
                <div class="field"><label>Label</label><input name="did_name" value="{{ old('did_name',$did->did_name) }}"></div>
            </div>
        </div>
        <div class="card"><h2>Routing destination</h2>
            <p class="muted" style="margin-top:-6px">For type <strong>CUSTOMER</strong>, the destination is one of the assigned account's SIP endpoints — start typing or pick from the list. For <strong>PSTN</strong> a number, for <strong>IP</strong> an <code>ip:port</code>.</p>
            <div class="grid3">
                <div class="field"><label>Primary type</label><select name="dst_type"><option value="">— none —</option>@foreach(['CUSTOMER','IP','PSTN'] as $t)<option value="{{ $t }}" @selected(old('dst_type',optional($did->destination)->dst_type)===$t)>{{ $t }}</option>@endforeach</select></div>
                <div class="field" style="grid-column:span 2"><label>Primary destination</label><input name="dst_destination" list="epList" autocomplete="off" value="{{ old('dst_destination',optional($did->destination)->dst_destination) }}" placeholder="SIP endpoint (CUSTOMER), IP:port, or PSTN number"></div>
            </div>
            <div class="grid3">
                <div class="field"><label>Failover type</label><select name="dst_type2"><option value="">— none —</option>@foreach(['CUSTOMER','IP','PSTN'] as $t)<option value="{{ $t }}" @selected(old('dst_type2',optional($did->destination)->dst_type2)===$t)>{{ $t }}</option>@endforeach</select></div>
                <div class="field" style="grid-column:span 2"><label>Failover destination</label><input name="dst_destination2" list="epList" autocomplete="off" value="{{ old('dst_destination2',optional($did->destination)->dst_destination2) }}"></div>
            </div>
            <datalist id="epList"></datalist>
            <p class="muted" id="epHint" style="font-size:12px"></p>
        </div>
        <button class="btn" type="submit">Save</button>
    </form>

    <style>
        .combo{position:relative}
        .combo-list{position:absolute;z-index:30;left:0;right:0;top:calc(100% + 2px);background:#fff;border:1px solid #cfdae4;
                    border-radius:7px;box-shadow:0 8px 24px rgba(16,36,55,.14);max-height:260px;overflow-y:auto}
        .combo-opt{padding:8px 11px;cursor:pointer;font-size:13.5px}
        .combo-opt:hover,.combo-opt.active{background:#eef5fd}
        .combo-opt .muted{font-size:12px}
    </style>

    <script>
    (function () {
        // searchable account combobox -> keeps hidden #acctSel (account_id) in sync
        var OPTS = @json($accountOptions ?? []);
        var hid = document.getElementById('acctSel');
        var box = document.getElementById('acctSearch');
        var list = document.getElementById('acctList');
        var UNASSIGNED = { id: '', label: '— unassigned —' };
        function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
        function labelFor(id){ if(!id) return ''; for(var i=0;i<OPTS.length;i++){ if(OPTS[i].id===id) return OPTS[i].label; } return id; }
        function render(q){
            q = (q||'').trim().toLowerCase();
            var rows = [UNASSIGNED].concat(OPTS).filter(function(o){ return !q || o.label.toLowerCase().indexOf(q) !== -1; });
            list.innerHTML = rows.map(function(o){ return '<div class="combo-opt" data-val="'+esc(o.id)+'">'+esc(o.label)+'</div>'; }).join('')
                || '<div class="combo-opt muted">No match</div>';
            list.hidden = false;
        }
        function pick(id){
            hid.value = id;
            box.value = id ? labelFor(id) : '';
            list.hidden = true;
            hid.dispatchEvent(new Event('change'));   // fire the routing-picker refresh
        }
        box.value = labelFor(hid.value);
        box.addEventListener('focus', function(){ render(box.value === labelFor(hid.value) ? '' : box.value); });
        box.addEventListener('input', function(){ render(box.value); });
        list.addEventListener('mousedown', function(e){ var o=e.target.closest('.combo-opt'); if(o && o.hasAttribute('data-val')){ e.preventDefault(); pick(o.getAttribute('data-val')); } });
        document.addEventListener('click', function(e){ if(!e.target.closest('.combo')) list.hidden = true; });
    })();
    </script>

    <script>
    (function () {
        // account_id -> [sip usernames]
        var EP = @json($endpointsByAccount ?? new stdClass);
        var sel = document.getElementById('acctSel');
        var list = document.getElementById('epList');
        var hint = document.getElementById('epHint');
        function refresh() {
            var acct = sel.value;
            var eps = (acct && EP[acct]) ? EP[acct] : [];
            list.innerHTML = eps.map(function (u) { return '<option value="' + u + '">'; }).join('');
            if (!acct) { hint.textContent = ''; }
            else if (eps.length) { hint.textContent = eps.length + ' endpoint(s) on ' + acct + ' — type CUSTOMER + pick one.'; }
            else { hint.textContent = 'Account ' + acct + ' has no SIP endpoints yet — create one first, then route the DID to it.'; }
        }
        sel.addEventListener('change', refresh);
        refresh();
    })();
    </script>
@endsection
