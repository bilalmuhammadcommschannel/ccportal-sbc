@extends('layouts.app')
@section('title','DIDs')
@section('content')
    <div class="rowbar"><h1 style="margin:0">DID numbers</h1>@can('create',App\Models\Ov500\Did::class)<a class="btn" href="{{ route('dids.create') }}">+ Add numbers</a>@endcan</div>
    <div class="card">
        <form method="GET" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap">
            <input type="text" name="q" value="{{ $q }}" placeholder="Search number" style="max-width:240px">
            <select name="status" style="max-width:160px"><option value="">All statuses</option>@foreach(['NEW','USED','DEAD','BLOCKED'] as $s)<option value="{{ $s }}" @selected($status===$s)>{{ $s }}</option>@endforeach</select>
            <button class="btn ghost sm">Filter</button>
        </form>
        <table>
            <thead><tr><th>Number</th><th>Type</th><th>Status</th><th>Assigned to</th><th>Channels</th><th></th></tr></thead>
            <tbody>
            @forelse($dids as $d)
                <tr>
                    <td><a href="{{ route('dids.show',$d) }}">{{ $d->did_number }}</a></td>
                    <td>{{ $d->number_type }}</td>
                    <td>@if($d->did_status==='USED')<span class="pill on">USED</span>@elseif($d->did_status==='NEW')<span class="pill off">NEW</span>@else<span class="pill off">{{ $d->did_status }}</span>@endif</td>
                    <td>
                        @if($d->account_id)
                            @php($nm = $assignedNames[$d->account_id] ?? null)
                            @if($nm){{ $nm }}<br><span class="muted" style="font-size:12px">{{ $d->account_id }}</span>@else{{ $d->account_id }}@endif
                        @else—@endif
                    </td>
                    <td>{{ $d->channels }}</td>
                    <td class="right"><a class="btn ghost sm" href="{{ route('dids.edit',$d) }}">Assign / edit</a></td>
                </tr>
            @empty<tr><td colspan="6" class="muted">No DIDs.</td></tr>@endforelse
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $dids->links() }}</div>
    </div>
@endsection
