@extends('layouts.app')
@section('title','Bundles')
@section('content')
    <div class="rowbar"><h1 style="margin:0">Bundle packages</h1><a class="btn" href="{{ route('bundles.create') }}">+ New bundle</a></div>
    <div class="card">
        <form method="GET" style="margin-bottom:16px;display:flex;gap:10px;max-width:420px"><input type="text" name="q" value="{{ $q }}" placeholder="Search name or ID"><button class="btn ghost sm">Search</button></form>
        <table>
            <thead><tr><th>Name</th><th>ID</th><th>Tiers</th><th>Assigned</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($bundles as $b)
                <tr>
                    <td><a href="{{ route('bundles.show',$b) }}">{{ $b->bundle_package_name }}</a></td>
                    <td class="muted">{{ $b->bundle_package_id }}</td>
                    <td class="muted">{{ $b->bundle1_type }}/{{ $b->bundle2_type }}/{{ $b->bundle3_type }}</td>
                    <td>{{ $b->assignments_count }}</td>
                    <td>@if((string)$b->bundle_package_status==='1')<span class="pill on">Active</span>@else<span class="pill off">Off</span>@endif</td>
                    <td class="right"><a class="btn ghost sm" href="{{ route('bundles.show',$b) }}">Manage</a></td>
                </tr>
            @empty<tr><td colspan="6" class="muted">No bundles.</td></tr>@endforelse
            </tbody>
        </table>
        <div style="margin-top:14px">{{ $bundles->links() }}</div>
    </div>
@endsection
