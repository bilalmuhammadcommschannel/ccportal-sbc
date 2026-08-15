@extends('layouts.app')
@section('title', $endpoint->username)
@section('content')
    <div class="rowbar"><h1 style="margin:0">{{ $endpoint->username }}</h1>
        <div><a class="btn ghost sm" href="{{ route('endpoints.index') }}">← All</a> <a class="btn sm" href="{{ route('endpoints.edit',$endpoint) }}">Edit</a></div>
    </div>

    {{--
      Device configuration panel. Operators kept typing the ACCOUNT ID into the
      softphone's username field, which can never authenticate — Kamailio only
      knows customer_sip_account.username. So the registration credentials get
      their own panel, labelled unambiguously, before anything else on the page.
    --}}
    <div class="card">
        <h2>Device configuration</h2>
        <p class="muted" style="margin-top:-6px">Enter these in the softphone or desk phone. The <strong>SIP username</strong> is not the account ID.</p>
        @if($endpoint->ipauthfrom === 'NO')
        <table>
            <tr><th style="width:200px">SIP username</th>
                <td><code class="cc-copyval">{{ $endpoint->username }}</code> <button type="button" class="btn ghost sm cc-copy" data-copy="{{ $endpoint->username }}">Copy</button>
                    <div class="muted" style="font-size:12px">Also goes in “Auth name” / “Authorization user” if the device has that field.</div></td></tr>
            <tr><th>Password</th><td>
                <code id="secret-val" class="cc-copyval" style="display:none"></code><span id="secret-mask">••••••</span>
                <button type="button" class="btn ghost sm" id="reveal-btn" data-url="{{ route('endpoints.secret',$endpoint) }}" style="margin-left:8px">Reveal</button>
                <button type="button" class="btn ghost sm cc-copy" id="secret-copy" style="display:none">Copy</button>
            </td></tr>
            <tr><th>Domain / Realm</th><td><code class="cc-copyval">{{ $sip['domain'] }}</code> <button type="button" class="btn ghost sm cc-copy" data-copy="{{ $sip['domain'] }}">Copy</button></td></tr>
            <tr><th>Proxy / Server</th><td><code class="cc-copyval">{{ $sip['proxy'] }}</code> <button type="button" class="btn ghost sm cc-copy" data-copy="{{ $sip['proxy'] }}">Copy</button>
                    <div class="muted" style="font-size:12px">Use as “Outbound proxy” / “SIP server” if the device separates it from the domain.</div></td></tr>
            <tr><th>Port / Transport</th><td>
                <code>{{ $sip['port'] }}</code> for UDP or TCP · <code>{{ $sip['tls_port'] }}</code> for TLS
                <div class="muted" style="font-size:12px">TLS is recommended. Register must be enabled on the account for any of this to work.</div></td></tr>
            <tr><th>Codecs to allow</th><td><code>{{ $endpoint->codecs }}</code></td></tr>
        </table>
        @else
            <p><strong>IP authentication</strong> — this endpoint has no username or password. Calls are accepted only from
                <code>{{ $endpoint->ipaddress }}</code> (matched on {{ $endpoint->ipauthfrom }}), sent to
                <code>{{ $sip['proxy'] }}:{{ $sip['port'] }}</code>.</p>
        @endif
    </div>

    <div class="card"><h2>Endpoint</h2>
        <table>
            <tr><th style="width:200px">SIP username</th><td><code>{{ $endpoint->username }}</code></td></tr>
            <tr><th>Display name</th><td>{{ $endpoint->display_name ?: '—' }} <span class="muted" style="font-size:12px">(cosmetic only — never used to register)</span></td></tr>
            <tr><th>Customer account</th><td>{{ $endpoint->account_id }} <span class="muted" style="font-size:12px">(billing account — not a login)</span></td></tr>
            <tr><th>Auth</th><td>
                @if($endpoint->ipauthfrom==='NO')
                    Password (see Device configuration above)
                @else
                    IP: {{ $endpoint->ipaddress }} ({{ $endpoint->ipauthfrom }})
                @endif
            </td></tr>
            <tr><th>Channels / CPS</th><td>{{ $endpoint->sip_cc }} / {{ $endpoint->sip_cps }}</td></tr>
            <tr><th>Codecs</th><td>{{ $endpoint->codecs }}</td></tr>
            <tr><th>Caller ID</th><td>{{ $endpoint->caller_id }} (prefer {{ $endpoint->cli_prefer }})</td></tr>
            <tr><th>Recording / DND</th><td>{{ (string)$endpoint->call_recording==='1'?'On':'Off' }} / {{ $endpoint->dnd }}</td></tr>
            <tr><th>Status</th><td>@if((string)$endpoint->status==='1')<span class="pill on">Active</span>@else<span class="pill off">Inactive</span>@endif</td></tr>
        </table>
    </div>

    <div class="card"><h2>Custom SIP headers</h2>
        @if($endpoint->headers->isNotEmpty())
        <table>
            <thead><tr><th>Header</th><th>Value</th><th>Direction</th></tr></thead>
            <tbody>@foreach($endpoint->headers as $h)<tr><td>{{ $h->header_name }}</td><td>{{ $h->header_value }}</td><td>{{ $h->direction }}</td></tr>@endforeach</tbody>
        </table>
        @else<p class="muted">None. Add them via <a href="{{ route('endpoints.edit',$endpoint) }}">Edit</a>.</p>@endif
    </div>

    <script>
    (function () {
        function copy(text, btn) {
            var done = function () { var t = btn.textContent; btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = t; }, 1200); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done, function () { btn.textContent = 'Copy failed'; });
                return;
            }
            // http fallback (clipboard API is https-only)
            var ta = document.createElement('textarea');
            ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); done(); } catch (e) { btn.textContent = 'Copy failed'; }
            document.body.removeChild(ta);
        }

        document.querySelectorAll('.cc-copy').forEach(function (b) {
            b.addEventListener('click', function () { copy(b.dataset.copy || '', b); });
        });

        var btn = document.getElementById('reveal-btn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            fetch(btn.dataset.url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
                .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
                .then(function (d) {
                    document.getElementById('secret-mask').style.display = 'none';
                    var v = document.getElementById('secret-val');
                    v.textContent = d.secret; v.style.display = 'inline';
                    btn.style.display = 'none';
                    var c = document.getElementById('secret-copy');
                    c.dataset.copy = d.secret; c.style.display = 'inline-block';
                })
                .catch(function () { btn.textContent = 'Error'; });
        });
    })();
    </script>
@endsection
