<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Comms Channel')</title>
    <style>
        :root{--rail:#1f2a37;--rail-hover:#27333f;--ink:#e8edf3;--dim:#9fb0c3;
              --blue:#1a5a9e;--blue-br:#4a8fd4;--line:#e2e8f0;--bg:#f1f5f9;--card:#fff;--text:#1f2a37}
        *{box-sizing:border-box}
        body{margin:0;font:14px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--text);background:var(--bg)}
        a{color:var(--blue);text-decoration:none}a:hover{text-decoration:underline}
        .app{display:flex;min-height:100vh}
        .rail{width:210px;background:var(--rail);color:var(--ink);flex-shrink:0;display:flex;flex-direction:column}
        .rail .brand{padding:18px 16px;font-weight:700;font-size:16px;border-bottom:1px solid #2f3d4d}
        .rail a{display:block;color:var(--ink);padding:10px 16px;border-left:3px solid transparent}
        .rail a:hover{background:var(--rail-hover);text-decoration:none}
        .rail a.active{background:rgba(26,90,158,.22);border-left-color:var(--blue-br);color:var(--blue-br);font-weight:600}
        .rail .spacer{flex:1}
        .rail form{padding:12px 16px;border-top:1px solid #2f3d4d}
        .rail .who{padding:12px 16px;color:var(--dim);font-size:12px;border-top:1px solid #2f3d4d}
        .main{flex:1;min-width:0;display:flex;flex-direction:column}
        .topbar{background:var(--blue);color:#fff;padding:12px 22px;font-weight:600}
        .content{padding:22px;max-width:1100px}
        .card{background:var(--card);border:1px solid var(--line);border-radius:8px;padding:20px;margin-bottom:18px}
        h1{font-size:20px;margin:0 0 16px}h2{font-size:15px;margin:0 0 12px}
        table{width:100%;border-collapse:collapse}
        th,td{text-align:left;padding:9px 10px;border-bottom:1px solid #eef2f6}
        th{background:#f1f5f9;color:#33414f;font-weight:600;font-size:12px;letter-spacing:.02em}
        tbody tr:hover{background:#f2f7fc}
        .btn{display:inline-block;background:var(--blue);color:#fff;padding:8px 14px;border-radius:6px;border:0;cursor:pointer;font:inherit}
        .btn:hover{background:#164e88;text-decoration:none}
        .btn.ghost{background:#fff;color:var(--blue);border:1px solid var(--blue)}
        .btn.sm{padding:5px 10px;font-size:13px}
        input,select{width:100%;padding:8px 10px;border:1px solid #cfdae4;border-radius:6px;font:inherit;background:#fff}
        label{display:block;font-size:12px;color:#556;margin:0 0 4px;font-weight:600}
        .field{margin-bottom:14px}
        .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
        .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
        .flash{background:#e6f4ea;border:1px solid #34a853;color:#1e6b34;padding:10px 14px;border-radius:6px;margin-bottom:16px}
        .errs{background:#fdecea;border:1px solid #d93025;color:#a50e0e;padding:10px 14px;border-radius:6px;margin-bottom:16px}
        .errs ul{margin:6px 0 0;padding-left:18px}
        .pill{display:inline-block;padding:2px 9px;border-radius:20px;font-size:12px;font-weight:600}
        .pill.on{background:#e6f4ea;color:#1e6b34}.pill.off{background:#f1f3f4;color:#5f6368}
        .muted{color:#5f6368}.right{text-align:right}
        .rowbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
        .ipset{border:1px dashed #cfdae4;border-radius:6px;padding:14px;margin-bottom:10px}
    </style>
</head>
<body>
<div class="app">
    <nav class="rail">
        <div class="brand">Comms&nbsp;Channel</div>
        @auth
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('carriers.index') }}" class="{{ request()->routeIs('carriers.*') ? 'active' : '' }}">Carriers</a>
                <a href="{{ route('tariffs.index') }}" class="{{ request()->routeIs('tariffs.*') ? 'active' : '' }}">Tariffs</a>
                <a href="{{ route('ratecards.index') }}" class="{{ request()->routeIs('ratecards.*') ? 'active' : '' }}">Ratecards</a>
                <a href="{{ route('bundles.index') }}" class="{{ request()->routeIs('bundles.*') ? 'active' : '' }}">Bundles</a>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">Users</a>
            @endif
            @if(in_array(auth()->user()->role, ['admin','reseller']))
                <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">Customers</a>
                <a href="{{ route('resellers.index') }}" class="{{ request()->routeIs('resellers.*') ? 'active' : '' }}">Resellers</a>
                <a href="{{ route('balances.index') }}" class="{{ request()->routeIs('balances.*') ? 'active' : '' }}">Balances</a>
                <a href="{{ route('dids.index') }}" class="{{ request()->routeIs('dids.*') ? 'active' : '' }}">DIDs</a>
            @endif
            <a href="{{ route('endpoints.index') }}" class="{{ request()->routeIs('endpoints.*') ? 'active' : '' }}">Endpoints</a>
            <a href="{{ route('cdrs.index') }}" class="{{ request()->routeIs('cdrs.*') ? 'active' : '' }}">CDRs</a>
            <div class="spacer"></div>
            <div class="who">{{ auth()->user()->email }}<br><span class="muted">{{ ucfirst(auth()->user()->role) }}</span><br><a href="{{ route('password.edit') }}" style="font-size:12px">Change password</a></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn ghost sm" style="width:100%">Log out</button></form>
        @endauth
    </nav>
    <div class="main">
        <div class="topbar">@yield('title', 'Comms Channel')</div>
        <div class="content">
            @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
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
