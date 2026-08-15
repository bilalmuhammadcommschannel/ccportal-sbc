@extends('layouts.app')
@section('title','Assign DID '.$did->did_number)
@section('content')
    <div class="rowbar"><h1 style="margin:0">Assign / edit {{ $did->did_number }}</h1><a class="btn ghost sm" href="{{ route('dids.show',$did) }}">← Back</a></div>
    <form method="POST" action="{{ route('dids.update',$did) }}">@csrf @method('PUT')
        <div class="card"><h2>Assignment</h2>
            <div class="grid">
                <div class="field"><label>Assigned account</label>
                    <select name="account_id"><option value="">— unassigned —</option>
                        @foreach($accounts as $a)<option value="{{ $a->account_id }}" @selected(old('account_id',$did->account_id)===$a->account_id)>{{ $a->account_id }} ({{ $a->account_type }})</option>@endforeach
                    </select>
                </div>
                <div class="field"><label>Status</label><select name="did_status">@foreach(['NEW','USED','DEAD','BLOCKED'] as $s)<option value="{{ $s }}" @selected(old('did_status',$did->did_status)===$s)>{{ $s }}</option>@endforeach</select></div>
            </div>
            <div class="grid">
                <div class="field"><label>Channels</label><input type="number" name="channels" value="{{ old('channels',$did->channels) }}" min="1"></div>
                <div class="field"><label>Label</label><input name="did_name" value="{{ old('did_name',$did->did_name) }}"></div>
            </div>
        </div>
        <div class="card"><h2>Routing destination</h2>
            <div class="grid3">
                <div class="field"><label>Primary type</label><select name="dst_type"><option value="">— none —</option>@foreach(['CUSTOMER','IP','PSTN'] as $t)<option value="{{ $t }}" @selected(old('dst_type',optional($did->destination)->dst_type)===$t)>{{ $t }}</option>@endforeach</select></div>
                <div class="field" style="grid-column:span 2"><label>Primary destination</label><input name="dst_destination" value="{{ old('dst_destination',optional($did->destination)->dst_destination) }}" placeholder="SIP user, IP:port, or PSTN number"></div>
            </div>
            <div class="grid3">
                <div class="field"><label>Failover type</label><select name="dst_type2"><option value="">— none —</option>@foreach(['CUSTOMER','IP','PSTN'] as $t)<option value="{{ $t }}" @selected(old('dst_type2',optional($did->destination)->dst_type2)===$t)>{{ $t }}</option>@endforeach</select></div>
                <div class="field" style="grid-column:span 2"><label>Failover destination</label><input name="dst_destination2" value="{{ old('dst_destination2',optional($did->destination)->dst_destination2) }}"></div>
            </div>
        </div>
        <button class="btn" type="submit">Save</button>
    </form>
@endsection
