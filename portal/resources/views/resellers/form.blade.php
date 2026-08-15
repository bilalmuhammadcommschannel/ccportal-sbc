@extends('layouts.app')
@section('title', $mode==='create'?'New reseller':'Edit reseller')
@section('content')
    @php $a=$mode==='create'?route('resellers.store'):route('resellers.update',$account); @endphp
    <div class="rowbar"><h1 style="margin:0">{{ $mode==='create'?'New reseller':'Edit '.optional($profile)->company_name }}</h1><a class="btn ghost sm" href="{{ route('resellers.index') }}">← Back</a></div>
    <form method="POST" action="{{ $a }}">@csrf @if($mode==='edit')@method('PUT')@endif
        <div class="card"><h2>Company</h2>
            <div class="grid">
                <div class="field"><label>Company name</label><input name="company_name" value="{{ old('company_name',optional($profile)->company_name) }}" required></div>
                <div class="field"><label>Contact name</label><input name="contact_name" value="{{ old('contact_name',optional($profile)->contact_name) }}"></div>
            </div>
            <div class="grid">
                <div class="field"><label>Email</label><input type="email" name="emailaddress" value="{{ old('emailaddress',optional($profile)->emailaddress) }}"></div>
                <div class="field"><label>Phone</label><input name="phone" value="{{ old('phone',optional($profile)->phone) }}"></div>
            </div>
        </div>
        <div class="card"><h2>Settings</h2>
            <div class="grid">
                <div class="field"><label>Currency</label><select name="currency_id" required>@foreach($currencies as $cur)<option value="{{ $cur->currency_id }}" @selected((int)old('currency_id',optional($account)->currency_id)===(int)$cur->currency_id)>{{ $cur->name }} ({{ $cur->symbol }})</option>@endforeach</select></div>
                @if($mode==='edit')
                <div class="field"><label>Status</label><select name="status_id"><option value="1" @selected((string)old('status_id',optional($account)->status_id)==='1')>Active</option><option value="0" @selected((string)old('status_id',optional($account)->status_id)==='0')>Closed</option><option value="-3" @selected((string)old('status_id',optional($account)->status_id)==='-3')>Blocked</option></select></div>
                @endif
            </div>
        </div>
        <button class="btn" type="submit">{{ $mode==='create'?'Create reseller':'Save changes' }}</button>
    </form>
@endsection
