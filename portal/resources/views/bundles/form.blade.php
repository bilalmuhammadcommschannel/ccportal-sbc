@extends('layouts.app')
@section('title', $mode==='create'?'New bundle':'Edit bundle')
@section('content')
    @php $a=$mode==='create'?route('bundles.store'):route('bundles.update',$bundle); @endphp
    <div class="rowbar"><h1 style="margin:0">{{ $mode==='create'?'New bundle':'Edit '.$bundle->bundle_package_name }}</h1><a class="btn ghost sm" href="{{ route('bundles.index') }}">← Back</a></div>
    <form method="POST" action="{{ $a }}">@csrf @if($mode==='edit')@method('PUT')@endif
        <div class="card"><h2>Package</h2>
            <div class="grid3">
                <div class="field"><label>Name</label><input name="bundle_package_name" value="{{ old('bundle_package_name',$bundle->bundle_package_name) }}" required></div>
                <div class="field"><label>Currency</label><select name="bundle_package_currency_id" required>@foreach($currencies as $cur)<option value="{{ $cur->currency_id }}" @selected((int)old('bundle_package_currency_id',$bundle->bundle_package_currency_id ?? 6)===(int)$cur->currency_id)>{{ $cur->name }} ({{ $cur->symbol }})</option>@endforeach</select></div>
                <div class="field"><label>Status</label><select name="bundle_package_status"><option value="1" @selected((string)old('bundle_package_status',$bundle->bundle_package_status)==='1')>Active</option><option value="0" @selected((string)old('bundle_package_status',$bundle->bundle_package_status)==='0')>Inactive</option></select></div>
            </div>
            <div class="grid3">
                <div class="field"><label>Monthly charge</label><input name="monthly_charges" value="{{ old('monthly_charges',$bundle->monthly_charges ?? 0) }}"></div>
                <div class="field"><label>Packaged</label><select name="package_option"><option value="0" @selected((string)old('package_option',$bundle->package_option ?? '0')==='0')>No</option><option value="1" @selected((string)old('package_option',$bundle->package_option)==='1')>Yes</option></select></div>
                <div class="field"><label>Bundle enabled</label><select name="bundle_option"><option value="1" @selected((string)old('bundle_option',$bundle->bundle_option ?? '1')==='1')>Yes</option><option value="0" @selected((string)old('bundle_option',$bundle->bundle_option)==='0')>No</option></select></div>
            </div>
            <div class="field"><label>Description</label><input name="bundle_package_description" value="{{ old('bundle_package_description',$bundle->bundle_package_description) }}"></div>
        </div>
        <div class="card"><h2>Tiers</h2>
            <p class="muted" style="margin-top:-6px">Each tier: MINUTE = inclusive minutes allowance, COST = inclusive spend allowance. Which prefixes draw from which tier is set on the bundle's page after saving.</p>
            @foreach([1,2,3] as $n)
            <div class="grid">
                <div class="field"><label>Tier {{ $n }} type</label><select name="bundle{{ $n }}_type"><option value="MINUTE" @selected(old("bundle{$n}_type",$bundle->{"bundle{$n}_type"} ?? 'MINUTE')==='MINUTE')>MINUTE</option><option value="COST" @selected(old("bundle{$n}_type",$bundle->{"bundle{$n}_type"})==='COST')>COST</option></select></div>
                <div class="field"><label>Tier {{ $n }} allowance</label><input name="bundle{{ $n }}_value" value="{{ old("bundle{$n}_value",$bundle->{"bundle{$n}_value"}) }}" placeholder="e.g. 1000"></div>
            </div>
            @endforeach
        </div>
        <button class="btn" type="submit">{{ $mode==='create'?'Create bundle':'Save changes' }}</button>
    </form>
@endsection
