<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('img/cc-favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/cc-favicon.png') }}">
    <title>@yield('title', 'Comms Channel')</title>
    <style>
        :root{
          /* brand */
          --brand:#0B5CAB; --brand-2:#2E7BC4; --brand-ink:#083f77;
          --rail:#0f1e30; --rail-2:#182c44; --ink:#e9f1f9; --dim:#8ba3bd;
          /* surfaces */
          --bg:#eef2f7; --card:#ffffff; --card-2:#f7fafc; --line:#e3e9f0; --line-2:#eef2f6;
          --text:#0f2437; --muted:#5b6b7c;
          /* semantic */
          --ok:#12855a; --ok-bg:#e6f5ee; --warn:#b57500; --warn-bg:#fdf2dc; --bad:#d24b4b; --bad-bg:#fdeceb;
          /* legacy aliases kept so existing pages are untouched */
          --blue:#0B5CAB; --blue-br:#5aa0e0;
          --radius:10px; --shadow:0 1px 2px rgba(16,36,55,.06),0 4px 16px rgba(16,36,55,.05);
        }
        *{box-sizing:border-box}
        body{margin:0;font:14px/1.55 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--text);background:var(--bg);
             -webkit-font-smoothing:antialiased}
        a{color:var(--brand);text-decoration:none}a:hover{text-decoration:underline}
        .app{display:flex;min-height:100vh}
        /* sidebar */
        .rail{width:216px;background:var(--rail);color:var(--ink);flex-shrink:0;display:flex;flex-direction:column;
              background-image:linear-gradient(180deg,#12233a 0%,#0d1a2b 100%)}
        .rail .brand{padding:16px 16px;border-bottom:1px solid #23384f}
        .rail .brand img{height:30px;display:block}
        .rail .brand .chip{background:#fff;border-radius:8px;padding:7px 10px;display:inline-block}
        .rail a{display:flex;align-items:center;gap:9px;color:var(--ink);padding:9px 16px;border-left:3px solid transparent;font-size:13.5px}
        .rail a:hover{background:var(--rail-2);text-decoration:none}
        .rail a.active{background:rgba(46,123,196,.20);border-left-color:var(--brand-2);color:#fff;font-weight:600}
        .rail a .ic{width:16px;text-align:center;opacity:.85;font-size:13px}
        .rail .spacer{flex:1}
        .rail form{padding:12px 16px;border-top:1px solid #23384f}
        .rail .who{padding:12px 16px;color:var(--dim);font-size:12px;border-top:1px solid #23384f}
        .main{flex:1;min-width:0;display:flex;flex-direction:column}
        .topbar{background:#fff;color:var(--text);padding:14px 26px;font-weight:700;font-size:16px;border-bottom:1px solid var(--line);
                display:flex;align-items:center;justify-content:space-between}
        .content{padding:24px 26px;max-width:1280px;width:100%}
        /* cards */
        .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:20px;margin-bottom:18px;box-shadow:var(--shadow)}
        h1{font-size:20px;margin:0 0 16px}h2{font-size:14px;margin:0 0 12px;font-weight:700;letter-spacing:.01em}
        .eyebrow{font-size:11px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--muted)}
        table{width:100%;border-collapse:collapse}
        th,td{text-align:left;padding:9px 10px;border-bottom:1px solid var(--line-2)}
        th{background:#f4f7fb;color:#3a4a5a;font-weight:600;font-size:11.5px;letter-spacing:.03em;text-transform:uppercase}
        tbody tr:hover{background:#f5f9fd}
        .num{font-variant-numeric:tabular-nums}
        /* buttons */
        .btn{display:inline-block;background:var(--brand);color:#fff;padding:8px 14px;border-radius:7px;border:0;cursor:pointer;font:inherit;font-weight:600}
        .btn:hover{background:var(--brand-ink);text-decoration:none}
        .btn.ghost{background:#fff;color:var(--brand);border:1px solid #cfe0f1}
        .btn.ghost:hover{background:#f2f8ff}
        .btn.sm{padding:5px 10px;font-size:13px;font-weight:600}
        .btn.danger{background:var(--bad)}.btn.danger:hover{background:#b83b3b}
        input,select{width:100%;padding:8px 10px;border:1px solid #cfdae4;border-radius:7px;font:inherit;background:#fff}
        input:focus,select:focus{outline:2px solid rgba(46,123,196,.35);outline-offset:0;border-color:var(--brand-2)}
        label{display:block;font-size:12px;color:#556;margin:0 0 4px;font-weight:600}
        .field{margin-bottom:14px}
        .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
        .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
        .flash{background:var(--ok-bg);border:1px solid #9dd6ba;color:#0e6b3f;padding:10px 14px;border-radius:8px;margin-bottom:16px}
        .errs{background:var(--bad-bg);border:1px solid #eeb4b1;color:#a3312e;padding:10px 14px;border-radius:8px;margin-bottom:16px}
        .errs ul{margin:6px 0 0;padding-left:18px}
        .pill{display:inline-block;padding:2px 9px;border-radius:20px;font-size:12px;font-weight:600}
        .pill.on{background:var(--ok-bg);color:var(--ok)}.pill.off{background:#eef1f4;color:#5f6368}
        .pill.warn{background:var(--warn-bg);color:var(--warn)}
        .muted{color:var(--muted)}.right{text-align:right}
        .rowbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px}
        .ipset{border:1px dashed #cfdae4;border-radius:8px;padding:14px;margin-bottom:10px}

        /* ---- dashboard: KPI cards ---- */
        .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-bottom:18px}
        .kpi{position:relative;display:block;background:var(--card);border:1px solid var(--line);border-radius:var(--radius);
             padding:16px 16px 15px;box-shadow:var(--shadow);overflow:hidden;color:var(--text);transition:transform .12s ease,box-shadow .12s ease}
        .kpi::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;background:var(--brand)}
        a.kpi:hover{text-decoration:none;transform:translateY(-2px);box-shadow:0 6px 22px rgba(16,36,55,.12)}
        a.kpi:hover .go{opacity:1;transform:translateX(0)}
        .kpi .go{position:absolute;top:14px;right:14px;color:var(--brand-2);opacity:0;transform:translateX(-4px);transition:.12s;font-weight:700}
        .kpi .k-label{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
        .kpi .k-val{font-size:30px;font-weight:800;line-height:1.05;margin:8px 0 2px;font-variant-numeric:tabular-nums;color:var(--text)}
        .kpi .k-sub{font-size:12px;color:var(--muted);font-variant-numeric:tabular-nums}
        .kpi.hero{background:var(--rail);border-color:var(--rail);color:#fff;background-image:linear-gradient(160deg,#14273f,#0d1a2b)}
        .kpi.hero::before{background:var(--brand-2)}
        .kpi.hero .k-label{color:#9db6d0}.kpi.hero .k-val{color:#fff}.kpi.hero .k-sub{color:#9db6d0}
        .kpi.money::before{background:var(--ok)}.kpi.money .k-val{color:var(--ok)}
        .kpi.sec::before{background:var(--warn)}
        .kpi.sec.clean::before{background:var(--ok)}
        .live-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#39d98a;margin-right:6px;vertical-align:middle;
                  box-shadow:0 0 0 0 rgba(57,217,138,.7);animation:pulse 1.8s infinite}
        @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(57,217,138,.6)}70%{box-shadow:0 0 0 7px rgba(57,217,138,0)}100%{box-shadow:0 0 0 0 rgba(57,217,138,0)}}
        @media (prefers-reduced-motion:reduce){.live-dot{animation:none}}

        /* ---- dashboard layout rows + charts ---- */
        .cols{display:grid;grid-template-columns:1fr 1fr;gap:18px}
        .cols.wide{grid-template-columns:1.5fr 1fr}
        @media (max-width:960px){.cols,.cols.wide{grid-template-columns:1fr}}
        .chart-svg{width:100%;height:auto;display:block}
        .legend{display:flex;flex-wrap:wrap;gap:10px 16px;margin-top:12px;font-size:12px}
        .legend .lk{display:flex;align-items:center;gap:6px;color:var(--muted)}
        .legend .sw{width:10px;height:10px;border-radius:3px;flex-shrink:0}
        .legend b{color:var(--text);font-variant-numeric:tabular-nums}
        .health{display:flex;gap:14px;flex-wrap:wrap}
        .gauge{flex:1;min-width:150px}
        .gauge .g-top{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px}
        .gauge .g-val{font-size:22px;font-weight:800;font-variant-numeric:tabular-nums}
        .svc{display:flex;flex-wrap:wrap;gap:6px}
        /* services as a traffic-light list */
        .svc-list{display:flex;flex-direction:column}
        .svc-row{display:flex;align-items:center;gap:11px;padding:9px 4px;border-bottom:1px solid var(--line-2)}
        .svc-row:last-child{border-bottom:0}
        .svc-row b{font-weight:600}
        .svc-row .st{margin-left:auto;font-size:12px}
        .light{width:12px;height:12px;border-radius:50%;flex:0 0 auto;background:#9aa7b4}
        .light.on{background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.18)}
        .light.off{background:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.18)}
        .light.unk{background:#c9a227;box-shadow:0 0 0 3px rgba(201,162,39,.18)}
        /* live-call direction pills */
        .pill.dir-in{background:#e7f0fb;color:var(--brand)}
        .pill.dir-out{background:#e9f5ee;color:var(--ok)}
        .kv{width:100%}.kv th{width:130px;background:transparent;text-transform:none;letter-spacing:0;color:var(--muted);font-weight:600}
        .kv td{font-variant-numeric:tabular-nums}
        .searchbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
        .searchbar input{max-width:280px}
        .chips{display:flex;gap:6px;flex-wrap:wrap}
        .chip-btn{border:1px solid #cfe0f1;background:#fff;color:var(--brand);padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer}
        .chip-btn.active{background:var(--brand);color:#fff;border-color:var(--brand)}
        .mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
    </style>
</head>
<body>
<div class="app">
    <nav class="rail">
        <div class="brand"><span class="chip"><img src="{{ asset('img/cc-logo.png') }}" alt="Comms Channel"></span></div>
        @auth
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="ic">◧</span> Dashboard</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('carriers.index') }}" class="{{ request()->routeIs('carriers.*') ? 'active' : '' }}"><span class="ic">⇄</span> Carriers</a>
                <a href="{{ route('tariffs.index') }}" class="{{ request()->routeIs('tariffs.*') ? 'active' : '' }}"><span class="ic">▤</span> Tariffs</a>
                <a href="{{ route('ratecards.index') }}" class="{{ request()->routeIs('ratecards.*') ? 'active' : '' }}"><span class="ic">▦</span> Ratecards</a>
                <a href="{{ route('bundles.index') }}" class="{{ request()->routeIs('bundles.*') ? 'active' : '' }}"><span class="ic">◲</span> Bundles</a>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}"><span class="ic">◔</span> Users</a>
                <a href="{{ route('firewall.index') }}" class="{{ request()->routeIs('firewall.*') ? 'active' : '' }}"><span class="ic">⛨</span> Firewall</a>
            @endif
            @if(in_array(auth()->user()->role, ['admin','reseller']))
                <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}"><span class="ic">☰</span> Customers</a>
                <a href="{{ route('resellers.index') }}" class="{{ request()->routeIs('resellers.*') ? 'active' : '' }}"><span class="ic">⊞</span> Resellers</a>
                <a href="{{ route('balances.index') }}" class="{{ request()->routeIs('balances.*') ? 'active' : '' }}"><span class="ic">$</span> Balances</a>
                <a href="{{ route('dids.index') }}" class="{{ request()->routeIs('dids.*') ? 'active' : '' }}"><span class="ic">#</span> DIDs</a>
            @endif
            <a href="{{ route('endpoints.index') }}" class="{{ request()->routeIs('endpoints.*') ? 'active' : '' }}"><span class="ic">☏</span> Endpoints</a>
            <a href="{{ route('cdrs.index') }}" class="{{ request()->routeIs('cdrs.*') ? 'active' : '' }}"><span class="ic">≡</span> CDRs</a>
            <div class="spacer"></div>
            <div class="who">{{ auth()->user()->email }}<br><span class="muted">{{ ucfirst(auth()->user()->role) }}</span><br><a href="{{ route('password.edit') }}" style="font-size:12px">Change password</a></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn ghost sm" style="width:100%">Log out</button></form>
        @endauth
    </nav>
    <div class="main">
        <div class="topbar"><span>@yield('title', 'Comms Channel')</span>@yield('topbar-right')</div>
        <div class="content">
            @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
            @if(session('fw_error'))<div class="errs">{{ session('fw_error') }}</div>@endif
            @if($errors->any())
                <div class="errs"><strong>Please fix the following:</strong>
                    <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
