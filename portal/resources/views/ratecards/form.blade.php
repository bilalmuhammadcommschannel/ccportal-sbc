@extends('layouts.app')
@section('title', $mode==='create'?'New ratecard':'Edit ratecard')
@section('content')
    @php $a=$mode==='create'?route('ratecards.store'):route('ratecards.update',$ratecard); @endphp
    <div class="rowbar"><h1 style="margin:0">{{ $mode==='create'?'New ratecard':'Edit '.$ratecard->ratecard_name }}</h1><a class="btn ghost sm" href="{{ route('ratecards.index') }}">← Back</a></div>
    <form method="POST" action="{{ $a }}">@csrf @if($mode==='edit')@method('PUT')@endif
        <div class="card"><h2>Ratecard</h2>
            <div class="grid">
                <div class="field"><label>Name</label><input name="ratecard_name" value="{{ old('ratecard_name',$ratecard->ratecard_name) }}" required></div>
                <div class="field"><label>Currency</label><select name="ratecard_currency_id" required>@foreach($currencies as $cur)<option value="{{ $cur->currency_id }}" @selected((int)old('ratecard_currency_id',$ratecard->ratecard_currency_id ?? 6)===(int)$cur->currency_id)>{{ $cur->name }} ({{ $cur->symbol }})</option>@endforeach</select></div>
            </div>
            <div class="grid">
                <div class="field"><label>Type @if($mode==='edit')<span class="muted">(fixed)</span>@endif</label>
                    <select name="ratecard_type" {{ $mode==='edit'?'disabled':'' }}>@foreach(['CUSTOMER','CARRIER'] as $t)<option value="{{ $t }}" @selected(old('ratecard_type',$ratecard->ratecard_type)===$t)>{{ $t }}</option>@endforeach</select>
                    @if($mode==='edit')<input type="hidden" name="ratecard_type" value="{{ $ratecard->ratecard_type }}">@endif
                </div>
                <div class="field"><label>Direction</label><select name="ratecard_for">@foreach(['OUTGOING','INCOMING'] as $d)<option value="{{ $d }}" @selected(old('ratecard_for',$ratecard->ratecard_for)===$d)>{{ $d }}</option>@endforeach</select></div>
            </div>
        </div>
        <button class="btn" type="submit">{{ $mode==='create'?'Create ratecard':'Save changes' }}</button>
    </form>
@endsection
