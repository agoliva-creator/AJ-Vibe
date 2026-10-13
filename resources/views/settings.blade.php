@extends('layout')
@section('content')
<h1>Store settings</h1><form method="post" class="card p-4" style="max-width:650px" action="{{ route('settings.update') }}">@csrf @method('PUT')
@foreach(['store_name'=>'Store name','tax_rate_bps'=>'Tax rate in basis points (1200 = 12%)','low_stock_threshold'=>'Low-stock alert threshold','shipping_fee_cents'=>'Flat shipping fee in cents (10000 = ₱100)'] as $key=>$label)
<label class="form-label mt-3" for="{{ $key }}">{{ $label }}</label><input class="form-control" id="{{ $key }}" name="{{ $key }}" value="{{ old($key,$settings[$key]) }}" type="{{ $key === 'store_name' ? 'text' : 'number' }}" required>
@endforeach
<p class="small text-muted mt-3">Tax is calculated after discounts and rounded to the nearest cent. The alert is queued when a sale takes stock from above the threshold to the threshold or below.</p><div><button class="btn btn-primary">Save settings</button></div></form>
@endsection
