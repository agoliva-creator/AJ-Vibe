<a class="btn btn-sm btn-outline-secondary" href="{{ route('records.index','coupons') }}">← Back to coupons</a>
<div class="coupon-page-heading"><p class="fashion-kicker">AJ Vibe / Promotions</p><h1>{{ $record->exists ? 'Edit coupon.' : 'Create a coupon.' }}</h1><p>Build a promotion with clear discounts, limits, and dates.</p></div>
<form class="card coupon-form-panel" method="post" action="{{ $record->exists ? route('records.update',['coupons',$record->id]) : route('records.store','coupons') }}">@csrf @if($record->exists) @method('PUT') @endif
<section><div class="coupon-section-title"><span>01</span><div><h2>Promotion details</h2><p>Choose a code and how the discount works.</p></div></div><div class="coupon-field-grid">
@foreach(['code'=>'Coupon code','type'=>'Discount type','value'=>'Discount value','active'=>'Availability'] as $field=>$label)
<div><label class="form-label" for="{{ $field }}">{{ $label }}</label>
@if(isset($options[$field]))
<select @class(['form-select','is-invalid'=>$errors->has($field)]) id="{{ $field }}" name="{{ $field }}" required>@foreach($options[$field] as $value=>$optionLabel)<option value="{{ $value }}" @selected((string)old($field,$record->$field ?? ($field === 'active' ? 1 : 'percent')) === (string)$value)>{{ $optionLabel }}</option>@endforeach</select>
@else
<input @class(['form-control','is-invalid'=>$errors->has($field)]) id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'value' ? 'number' : 'text' }}" value="{{ old($field,$record->$field) }}" @if($field === 'value') min="1" step="1" @endif required>
@endif
@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
@endforeach
</div><p class="coupon-field-help">Percent discounts: 1–100. Fixed discounts use cents: 100 = ₱1.00.</p></section>
<section><div class="coupon-section-title"><span>02</span><div><h2>Limits & validity</h2><p>Control when and how often your coupon can be used.</p></div></div><div class="coupon-field-grid">
@foreach(['minimum_cents'=>'Minimum purchase (cents)','usage_limit'=>'Maximum uses (optional)','starts_at'=>'Starts at (optional)','expires_at'=>'Expires at (optional)'] as $field=>$label)
<div><label class="form-label" for="{{ $field }}">{{ $label }}</label><input @class(['form-control','is-invalid'=>$errors->has($field)]) id="{{ $field }}" name="{{ $field }}" type="{{ in_array($field,['starts_at','expires_at']) ? 'datetime-local' : 'number' }}" value="{{ old($field,in_array($field,['starts_at','expires_at']) ? $record->$field?->format('Y-m-d\TH:i') : ($record->$field ?? ($field === 'minimum_cents' ? 0 : ''))) }}" @if(!in_array($field,['starts_at','expires_at'])) min="0" step="1" @endif @required($field === 'minimum_cents')>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
@endforeach
</div><p class="coupon-field-help">Leave optional fields blank for no usage limit or date restriction.</p></section>
<div class="coupon-form-actions"><a class="btn btn-outline-secondary" href="{{ route('records.index','coupons') }}">Cancel</a><button class="btn btn-primary" type="submit">{{ $record->exists ? 'Save changes' : 'Create coupon' }} →</button></div>
</form>
