@extends('layouts.app')
@section('title', $bundle->bundle_package_name)
@section('content')
    <div class="rowbar"><h1 style="margin:0">{{ $bundle->bundle_package_name }}</h1>
        <div><a class="btn ghost sm" href="{{ route('bundles.index') }}">← All</a> <a class="btn sm" href="{{ route('bundles.edit',$bundle) }}">Edit</a></div>
    </div>
    <div class="card"><h2>Tiers</h2>
        <table>
            <thead><tr><th>Tier</th><th>Type</th><th>Allowance</th></tr></thead>
            <tbody>
                @foreach([1,2,3] as $n)
                <tr><td>{{ $n }}</td><td>{{ $bundle->{"bundle{$n}_type"} }}</td><td>{{ rtrim(rtrim(number_format((float)$bundle->{"bundle{$n}_value"},6),'0'),'.') ?: '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card"><h2>Prefixes → tier</h2>
        <form method="POST" action="{{ route('bundles.addPrefix',$bundle) }}" style="display:flex;gap:10px;align-items:end;margin-bottom:14px">@csrf
            <div class="field" style="margin:0"><label>Prefix</label><input name="prefix" placeholder="6141" style="max-width:160px"></div>
            <div class="field" style="margin:0"><label>Tier</label><select name="bundle_id"><option value="1">1</option><option value="2">2</option><option value="3">3</option></select></div>
            <button class="btn" type="submit">Add</button>
        </form>
        <table>
            <thead><tr><th>Prefix</th><th>Tier</th><th></th></tr></thead>
            <tbody>
            @forelse($bundle->prefixes as $p)
                <tr><td>{{ $p->prefix }}</td><td>{{ $p->bundle_id }}</td><td class="right"><form method="POST" action="{{ route('bundles.removePrefix',[$bundle,$p]) }}">@csrf @method('DELETE')<button class="btn ghost sm">✕</button></form></td></tr>
            @empty<tr><td colspan="3" class="muted">No prefixes mapped.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="card"><h2>Assigned accounts ({{ $bundle->assignments->count() }})</h2>
        <form method="POST" action="{{ route('bundles.assign',$bundle) }}" style="display:flex;gap:10px;align-items:end;margin-bottom:14px">@csrf
            <div class="field" style="margin:0"><label>Account</label>
                <select name="account_id" required><option value="">— select —</option>@foreach($assignable as $a)<option value="{{ $a->account_id }}">{{ $a->account_id }} ({{ $a->account_type }})</option>@endforeach</select>
            </div>
            <button class="btn" type="submit">Assign</button>
        </form>
        <table>
            <thead><tr><th>Account</th><th>Assigned</th><th></th></tr></thead>
            <tbody>
            @forelse($bundle->assignments as $asg)
                <tr><td>{{ $asg->account_id }}</td><td class="muted">{{ $asg->assign_dt }}</td><td class="right"><form method="POST" action="{{ route('bundles.unassign',[$bundle,$asg]) }}" onsubmit="return confirm('Unassign?')">@csrf @method('DELETE')<button class="btn ghost sm">Unassign</button></form></td></tr>
            @empty<tr><td colspan="3" class="muted">Not assigned to any account.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
@endsection
