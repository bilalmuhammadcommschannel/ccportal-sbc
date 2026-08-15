@extends('layouts.app')
@section('title', 'Bulk import · '.$ratecard->ratecard_name)
@section('content')
    <div class="rowbar"><h1 style="margin:0">Bulk rate import</h1><a class="btn ghost sm" href="{{ route('ratecards.show',$ratecard) }}">← Back to {{ $ratecard->ratecard_name }}</a></div>
    <div class="card">
        <h2>Upload CSV</h2>
        <p>Header row required. Columns (required first):</p>
        <pre style="background:#f1f5f9;padding:12px;border-radius:6px;overflow-x:auto">prefix,destination,rate,minimal_time,resolution_time,setup_charge,connection_charge,inclusive_channel,exclusive_per_channel_rental,grace_period</pre>
        <p class="muted">Required: <strong>prefix, destination, rate</strong>. The rest are optional (defaults: pulse 1/1, charges 0, 1 inclusive channel). Existing prefixes in this ratecard are updated; new ones inserted. The whole file imports in one transaction — if it can't all commit, nothing changes. Max 20,000 rows.</p>
        <form method="POST" action="{{ route('rates.bulk.import',$ratecard) }}" enctype="multipart/form-data">@csrf
            <div class="field"><label>CSV file</label><input type="file" name="csv" accept=".csv,text/csv" required></div>
            <button class="btn" type="submit">Import rates</button>
        </form>
    </div>
@endsection
