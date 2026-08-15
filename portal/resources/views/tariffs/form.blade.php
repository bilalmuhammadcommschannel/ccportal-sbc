@extends('layouts.app')
@section('title', $mode==='create'?'New tariff':'Edit tariff')
@section('content')
    @php $a = $mode==='create' ? route('tariffs.store') : route('tariffs.update',$tariff); @endphp
    <div class="rowbar"><h1 style="margin:0">{{ $mode==='create'?'New tariff':'Edit '.$tariff->tariff_name }}</h1><a class="btn ghost sm" href="{{ route('tariffs.index') }}">← Back</a></div>
    <form method="POST" action="{{ $a }}">@csrf @if($mode==='edit')@method('PUT')@endif
        <div class="card"><h2>Tariff</h2>
            <div class="grid">
                <div class="field"><label>Name</label><input name="tariff_name" value="{{ old('tariff_name',$tariff->tariff_name) }}" required></div>
                <div class="field"><label>Type</label>
                    <select name="tariff_type" {{ $mode==='edit'?'disabled':'' }}>
                        @foreach(['CUSTOMER','CARRIER'] as $t)<option value="{{ $t }}" @selected(old('tariff_type',$tariff->tariff_type)===$t)>{{ $t }}</option>@endforeach
                    </select>
                    @if($mode==='edit')<input type="hidden" name="tariff_type" value="{{ $tariff->tariff_type }}">@endif
                </div>
            </div>
            <div class="grid3">
                <div class="field"><label>Currency ID</label><input type="number" name="tariff_currency_id" value="{{ old('tariff_currency_id',$tariff->tariff_currency_id ?? 1) }}" required></div>
                <div class="field"><label>Status</label><select name="tariff_status"><option value="1" @selected((string)old('tariff_status',$tariff->tariff_status)==='1')>Active</option><option value="0" @selected((string)old('tariff_status',$tariff->tariff_status)==='0')>Inactive</option></select></div>
                <div class="field"><label>Monthly charge</label><input name="monthly_charges" value="{{ old('monthly_charges',$tariff->monthly_charges ?? 0) }}"></div>
            </div>
            <div class="field"><label>Description</label><input name="tariff_description" value="{{ old('tariff_description',$tariff->tariff_description) }}"></div>
        </div>
        <div class="card"><h2>Bundle / flat-rate (optional)</h2>
            <div class="grid">
                <div class="field"><label>Packaged tariff</label><select name="package_option"><option value="0" @selected((string)old('package_option',$tariff->package_option ?? '0')==='0')>No</option><option value="1" @selected((string)old('package_option',$tariff->package_option)==='1')>Yes</option></select></div>
                <div class="field"><label>Bundle enabled</label><select name="bundle_option"><option value="0" @selected((string)old('bundle_option',$tariff->bundle_option ?? '0')==='0')>No</option><option value="1" @selected((string)old('bundle_option',$tariff->bundle_option)==='1')>Yes</option></select></div>
            </div>
            @foreach([1,2,3] as $n)
            <div class="grid">
                <div class="field"><label>Bundle {{ $n }} type</label><select name="bundle{{ $n }}_type"><option value="MINUTE" @selected(old("bundle{$n}_type",$tariff->{"bundle{$n}_type"} ?? 'MINUTE')==='MINUTE')>MINUTE</option><option value="COST" @selected(old("bundle{$n}_type",$tariff->{"bundle{$n}_type"})==='COST')>COST</option></select></div>
                <div class="field"><label>Bundle {{ $n }} value</label><input name="bundle{{ $n }}_value" value="{{ old("bundle{$n}_value",$tariff->{"bundle{$n}_value"}) }}"></div>
            </div>
            @endforeach
        </div>
        <button class="btn" type="submit">{{ $mode==='create'?'Create tariff':'Save changes' }}</button>
    </form>
@endsection
