@extends('layouts.app')
@section('title', $mode==='create'?'New customer':'Edit customer')
@section('content')
    @php $a=$mode==='create'?route('customers.store'):route('customers.update',$account); @endphp
    <div class="rowbar"><h1 style="margin:0">{{ $mode==='create'?'New customer':'Edit '.optional($profile)->company_name }}</h1><a class="btn ghost sm" href="{{ route('customers.index') }}">← Back</a></div>
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
        <div class="card"><h2>Billing</h2>
            <div class="grid3">
                <div class="field"><label>Currency</label><select name="currency_id" required>@foreach($currencies as $cur)<option value="{{ $cur->currency_id }}" @selected((int)old('currency_id',optional($account)->currency_id)===(int)$cur->currency_id)>{{ $cur->name }} ({{ $cur->symbol }})</option>@endforeach</select></div>
                <div class="field"><label>Billing type</label><select name="billing_type">@foreach(['prepaid','postpaid','netoff'] as $b)<option value="{{ $b }}" @selected(old('billing_type',optional($profile)->billing_type ?? 'prepaid')===$b)>{{ $b }}</option>@endforeach</select></div>
                <div class="field"><label>Billing cycle</label><select name="billing_cycle">@foreach(['monthly','weekly'] as $b)<option value="{{ $b }}" @selected(old('billing_cycle',optional($profile)->billing_cycle ?? 'monthly')===$b)>{{ $b }}</option>@endforeach</select></div>
            </div>
            <div class="grid3">
                <div class="field"><label>Max channels</label><input type="number" name="account_cc" value="{{ old('account_cc',optional($account)->account_cc) }}"></div>
                <div class="field"><label>Max CPS</label><input type="number" name="account_cps" value="{{ old('account_cps',optional($account)->account_cps) }}"></div>
                <div class="field"><label>Tariff</label><select name="tariff_id"><option value="">— none —</option>@foreach($tariffs as $tid=>$tn)<option value="{{ $tid }}" @selected(old('tariff_id',$tariffId)===$tid)>{{ $tn }}</option>@endforeach</select></div>
            </div>
            @if($mode==='edit')
            <div class="field" style="max-width:240px"><label>Status</label><select name="status_id"><option value="1" @selected((string)old('status_id',optional($account)->status_id)==='1')>Active</option><option value="0" @selected((string)old('status_id',optional($account)->status_id)==='0')>Closed</option><option value="-3" @selected((string)old('status_id',optional($account)->status_id)==='-3')>Blocked</option></select></div>
            @endif
        </div>
        <button class="btn" type="submit">{{ $mode==='create'?'Create customer':'Save changes' }}</button>
    </form>
@endsection
