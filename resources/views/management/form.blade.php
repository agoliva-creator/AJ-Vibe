@extends('layout')
@section('content')
@if($entity === 'coupons')
@include('management.coupon-form')
@else
<a class="management-back-button btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('records.index',$entity) }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12H4m6-6-6 6 6 6"/></svg><span>Back to {{ $entity }}</span></a><h1 class="mt-3">{{ $record->exists ? 'Edit' : 'Add' }} {{ \Illuminate\Support\Str::singular($entity) }}</h1><form class="card p-4 mt-4" style="max-width:760px" method="post" enctype="multipart/form-data" action="{{ $record->exists ? route('records.update',[$entity,$record->id]) : route('records.store',$entity) }}">@csrf
@if($record->exists)
@method('PUT')
@endif
@foreach($fields as $field)
<label class="form-label mt-3" for="{{ $field }}">{{ ucfirst(str_replace('_',' ',str_replace('_id','',$field))) }}</label>
@if(isset($options[$field]))
<select class="form-select" name="{{ $field }}" id="{{ $field }}" required><option value="">Select</option>
@foreach($options[$field] as $value=>$label)
<option value="{{ $value }}" @selected((string)old($field,$record->$field) === (string)$value)>{{ $label }}</option>
@endforeach
</select>
@elseif(in_array($field,['description']))
<textarea class="form-control" id="{{ $field }}" name="{{ $field }}">{{ old($field,$record->$field) }}</textarea>
@else
<input class="form-control" id="{{ $field }}" name="{{ $field }}" type="{{ match($field) {'email'=>'email','password'=>'password','starts_at','expires_at'=>'datetime-local','price','stock','value','minimum_cents','usage_limit'=>'number',default=>'text'} }}" value="{{ $field === 'password' ? '' : old($field,in_array($field,['starts_at','expires_at']) ? $record->$field?->format('Y-m-d\TH:i') : $record->$field) }}" @if($field === 'price') step="0.01" min="0" @endif @if(in_array($field,['stock','value','minimum_cents','usage_limit'])) step="1" min="0" @endif @required(!in_array($field,['password','description','slug','usage_limit','starts_at','expires_at'])) @if($field === 'password') autocomplete="new-password" @endif>
@endif
@endforeach
@if(in_array($entity,['products','users']))
@if($entity === 'products')
<label class="form-label mt-4" for="variant_rows">Sizes, colors & stock</label><textarea class="form-control" name="variant_rows" id="variant_rows" rows="5" placeholder="M | Black | 10&#10;L | Black | 8&#10;M | White | 12">{{ old('variant_rows', $record->exists ? $record->variants->where('active',true)->map(fn($variant)=>$variant->size.' | '.$variant->color.' | '.$variant->stock)->join("\n") : '') }}</textarea><p class="small text-muted mt-2">One combination per line: Size | Color | Stock. Leave blank for products without variants. For variant products, total stock is calculated automatically. Removed combinations become unavailable.</p>
@endif
@php($currentImage = $entity === 'products' ? $record->image_path : $record->avatar_path)
@if($entity === 'users' && !$currentImage)
<img id="upload-preview" class="upload-preview d-block mt-3 {{ $entity === 'users' ? 'profile-circle' : '' }}" src="{{ asset('images/default-avatar.svg') }}" alt="Default profile photo">
@else
<img id="upload-preview" class="upload-preview d-block mt-3 {{ $entity === 'users' ? 'profile-circle' : '' }}" @if($currentImage) src="{{ app(\App\Services\ImageService::class)->url($currentImage) }}" @else hidden @endif alt="{{ $entity === 'products' ? 'Product image preview' : 'Profile photo preview' }}">
@endif
<label class="form-label mt-3" for="upload">{{ $entity === 'products' ? 'Product image' : 'Avatar' }}</label><input class="form-control" type="file" id="upload" name="{{ $entity === 'products' ? 'image' : 'avatar' }}" accept="image/jpeg,image/png,image/webp"><p class="small text-muted mt-2">JPEG, PNG, or WebP, up to 2 MB. Leave blank to keep the current image.</p>
@endif
@if($entity === 'coupons')
<p class="small text-muted mt-3">Percentage values are 1–100. Fixed discounts and minimum purchase amounts use cents: 100 = ₱1.00.</p>
@endif
@if($entity === 'users')
<p class="small text-muted mt-3">New passwords require 12 characters with upper and lowercase letters and numbers. Leave blank when editing to keep the password.</p>
@endif
<div class="mt-4"><button class="btn btn-primary">Save record</button> <a class="btn btn-outline-secondary" href="{{ route('records.index',$entity) }}">Cancel</a></div></form>
@endif
@endsection
