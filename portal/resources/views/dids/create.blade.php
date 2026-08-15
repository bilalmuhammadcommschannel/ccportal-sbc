@extends('layouts.app')
@section('title','Add DIDs')
@section('content')
    <div class="rowbar"><h1 style="margin:0">Add DID numbers</h1><a class="btn ghost sm" href="{{ route('dids.index') }}">← Back</a></div>
    <form method="POST" action="{{ route('dids.store') }}">@csrf
        <div class="card"><h2>Numbers</h2>
            <div class="field"><label>Mode</label>
                <select name="mode" onchange="document.getElementById('single').style.display=this.value==='single'?'block':'none';document.getElementById('range').style.display=this.value==='range'?'block':'none'">
                    <option value="single">Single number</option><option value="range">Contiguous range</option>
                </select>
            </div>
            <div id="single"><div class="field"><label>DID number</label><input name="did_number" value="{{ old('did_number') }}" placeholder="61280000000"></div></div>
            <div id="range" style="display:none"><div class="grid">
                <div class="field"><label>From</label><input name="range_from" value="{{ old('range_from') }}" placeholder="61280000000"></div>
                <div class="field"><label>To</label><input name="range_to" value="{{ old('range_to') }}" placeholder="61280000099"></div>
            </div><p class="muted">Same length, ascending, max 1000 per batch.</p></div>
            <div class="grid3">
                <div class="field"><label>Type</label><select name="number_type"><option value="DID">DID</option><option value="TFN">TFN (toll-free)</option></select></div>
                <div class="field"><label>Channels</label><input type="number" name="channels" value="{{ old('channels',2) }}" min="1"></div>
                <div class="field"><label>Label (optional)</label><input name="did_name" value="{{ old('did_name') }}"></div>
            </div>
        </div>
        <button class="btn" type="submit">Add to inventory</button>
    </form>
@endsection
