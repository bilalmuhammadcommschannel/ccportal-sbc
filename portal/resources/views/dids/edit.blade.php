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
                @if(auth()->user()->isAdmin())
                <div class="field"><label>Provider carrier <span class="muted">(who delivers inbound)</span></label>
                    <select name="carrier_id"><option value="">— none —</option>
                        @foreach($carriers as $cr)<option value="{{ $cr->carrier_id }}" @selected(old('carrier_id',$did->carrier_id)===$cr->carrier_id)>{{ $cr->carrier_name }} ({{ $cr->carrier_type }})</option>@endforeach
                    </select>
                </div>
                @endif
            </div>
            <div class="grid">
                <div class="field"><label>Label</label><input name="did_name" value="{{ old('did_name',$did->did_name) }}"></div>
            </div>
        </div>
        <div class="card"><h2>Routing destination</h2>
            <p class="muted" style="margin-top:-6px">For type <strong>CUSTOMER</strong>, pick one of the assigned account's SIP endpoints from the dropdown. For <strong>PSTN</strong> enter a number, for <strong>IP</strong> an <code>ip:port</code>.</p>
            <div class="grid3">
                <div class="field"><label>Primary type</label><select name="dst_type" id="dstType1">@foreach(['','CUSTOMER','IP','PSTN'] as $t)<option value="{{ $t }}" @selected(old('dst_type',optional($did->destination)->dst_type)===$t)>{{ $t ?: '— none —' }}</option>@endforeach</select></div>
                <div class="field" style="grid-column:span 2"><label>Primary destination</label>
                    {{-- CUSTOMER -> real endpoint dropdown; IP/PSTN -> free text. Only the
                         active one is enabled, so only it submits (dst_destination). --}}
                    <select name="dst_destination" id="dstSel1" hidden disabled></select>
                    <input  name="dst_destination" id="dstTxt1" autocomplete="off" value="{{ old('dst_destination',optional($did->destination)->dst_destination) }}" placeholder="IP:port or PSTN number">
                </div>
            </div>
            <div class="grid3">
                <div class="field"><label>Failover type</label><select name="dst_type2" id="dstType2">@foreach(['','CUSTOMER','IP','PSTN'] as $t)<option value="{{ $t }}" @selected(old('dst_type2',optional($did->destination)->dst_type2)===$t)>{{ $t ?: '— none —' }}</option>@endforeach</select></div>
                <div class="field" style="grid-column:span 2"><label>Failover destination</label>
                    <select name="dst_destination2" id="dstSel2" hidden disabled></select>
                    <input  name="dst_destination2" id="dstTxt2" autocomplete="off" value="{{ old('dst_destination2',optional($did->destination)->dst_destination2) }}">
                </div>
            </div>
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
        var acctSel = document.getElementById('acctSel');
        var hint = document.getElementById('epHint');
        // saved destinations, so a CUSTOMER endpoint stays selected on load
        var SAVED = {
            1: @json(old('dst_destination',  optional($did->destination)->dst_destination)),
            2: @json(old('dst_destination2', optional($did->destination)->dst_destination2))
        };
        function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
        function epsFor(){ var a=acctSel.value; return (a && EP[a]) ? EP[a] : []; }
        // (re)build one destination <select> from the current account's endpoints,
        // keeping the saved value selected (even if it is no longer an endpoint).
        function fillSelect(i) {
            var sel = document.getElementById('dstSel'+i);
            var saved = SAVED[i] || '', eps = epsFor(), has = false;
            var html = '<option value="">— select endpoint —</option>';
            eps.forEach(function(u){ has = has || (u===saved); html += '<option value="'+esc(u)+'"'+(u===saved?' selected':'')+'>'+esc(u)+'</option>'; });
            if (saved && !has) html += '<option value="'+esc(saved)+'" selected>'+esc(saved)+' (not a current endpoint)</option>';
            sel.innerHTML = html;
        }
        // show the dropdown for CUSTOMER, the text box for IP/PSTN/none; disable the
        // hidden one so exactly one field submits under each name.
        function toggle(i) {
            var isCust = document.getElementById('dstType'+i).value === 'CUSTOMER';
            var sel = document.getElementById('dstSel'+i), txt = document.getElementById('dstTxt'+i);
            sel.hidden = !isCust; sel.disabled = !isCust;
            txt.hidden =  isCust; txt.disabled =  isCust;
        }
        function refreshHint() {
            var a = acctSel.value, eps = epsFor();
            if (!a) hint.textContent = '';
            else if (eps.length) hint.textContent = eps.length + ' endpoint(s) on ' + a + ' — choose type CUSTOMER to pick one.';
            else hint.textContent = 'Account ' + a + ' has no SIP endpoints yet — create one first, then route the DID to it.';
        }
        function refreshAll(){ fillSelect(1); fillSelect(2); toggle(1); toggle(2); refreshHint(); }
        acctSel.addEventListener('change', refreshAll);   // fired by the account combobox on pick
        document.getElementById('dstType1').addEventListener('change', function(){ toggle(1); });
        document.getElementById('dstType2').addEventListener('change', function(){ toggle(2); });
        refreshAll();
    })();
    </script>
@endsection
