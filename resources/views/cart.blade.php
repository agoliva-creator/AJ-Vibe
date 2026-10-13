@extends('layout')
@section('title', 'Your shopping bag · AJ Vibe')
@section('content')
<div class="bag-heading"><div><p class="fashion-kicker mb-2">AJ Vibe / Your selection</p><h1>Your shopping bag.</h1><p>Good choices. One step closer to your next look.</p></div><a class="fashion-outline-button" href="{{ route('catalog.index') }}">Continue shopping ↗</a></div>
<div class="bag-layout"><section class="card bag-products" aria-labelledby="bag-items-title"><div class="bag-section-header"><h2 id="bag-items-title">Your items</h2><span>{{ count($summary['items']) }} {{ count($summary['items']) === 1 ? 'product' : 'products' }}</span></div>
@forelse($summary['items'] as $item)
<article class="bag-item"><div class="bag-photo">
@if($item['product']->image_path)
<img src="{{ app(\App\Services\ImageService::class)->url($item['product']->image_path) }}" alt="{{ $item['product']->name }}" loading="lazy">
@else
<span aria-hidden="true">◇</span>
@endif
</div><div class="bag-item-content"><div class="bag-item-heading"><div><p class="bag-category">{{ $item['product']->category->name }}</p><h3>{{ $item['product']->name }}</h3>@if($item['variant'])<p class="bag-unit-price">{{ $item['variant']->label() }}</p>@endif<p class="bag-unit-price">₱{{ \App\Services\Money::decimal(\App\Services\Money::cents($item['product']->price)) }} / item</p></div><strong>₱{{ \App\Services\Money::decimal($item['line_total_cents']) }}</strong></div>
@if(!$item['available'])
<p class="bag-unavailable">This quantity is no longer available. Update or remove this item before checkout.</p>
@else
<p class="bag-available"><span aria-hidden="true">✓</span> Available for your order</p>
@endif
<div class="bag-controls"><form class="bag-quantity" method="post" action="{{ route('cart.set') }}">@csrf<input type="hidden" name="product_id" value="{{ $item['product']->id }}">@if($item['variant_id'])<input type="hidden" name="original_variant_id" value="{{ $item['variant_id'] }}"><label class="visually-hidden" for="cart-variant-{{ $item['product']->id }}-{{ $item['variant_id'] }}">Size and color for {{ $item['product']->name }}</label><select class="form-select cart-variant-select" id="cart-variant-{{ $item['product']->id }}-{{ $item['variant_id'] }}" name="variant_id" required>@foreach($item['product']->variants as $option)<option value="{{ $option->id }}" @selected($option->id === $item['variant_id']) @disabled((!$option->active || !$option->stock) && $option->id !== $item['variant_id'])>{{ $option->label() }} · {{ $option->stock }} left{{ !$option->active ? ' (unavailable)' : '' }}</option>@endforeach</select>@endif<label for="quantity-{{ $item['product']->id }}-{{ $item['variant_id'] ?? 'base' }}">Qty</label><input class="form-control" id="quantity-{{ $item['product']->id }}-{{ $item['variant_id'] ?? 'base' }}" type="number" name="quantity" min="0" max="1000" value="{{ $item['quantity'] }}" required><button class="btn btn-outline-secondary" type="submit">Update</button></form><form method="post" action="{{ route('cart.set') }}">@csrf<input type="hidden" name="product_id" value="{{ $item['product']->id }}"><input type="hidden" name="variant_id" value="{{ $item['variant_id'] }}"><input type="hidden" name="quantity" value="0"><button class="bag-remove" type="submit" aria-label="Remove {{ $item['product']->name }}">Remove</button></form></div></div></article>
@empty
<div class="bag-empty"><svg viewBox="0 0 64 72" aria-hidden="true"><path d="M12 22h40l4 44H8z"/><path d="M22 25V16a10 10 0 0 1 20 0v9"/></svg><h3>Your next look starts here.</h3><p>Your bag is empty. Find the pieces that feel like you.</p><a class="fashion-button" href="{{ route('catalog.index') }}">Explore the collection ↗</a></div>
@endforelse
@if(count($summary['items']))
<div class="bag-footnote">Prices and stock are checked again when you place your order.</div>
@endif
</section><aside class="card bag-summary" aria-labelledby="bag-summary-title"><div class="bag-section-header"><h2 id="bag-summary-title">Order summary</h2><span>AJ / VIBE</span></div><div class="bag-summary-content"><dl class="bag-totals"><div><dt>Subtotal</dt><dd>₱{{ \App\Services\Money::decimal($summary['subtotal_cents']) }}</dd></div><div><dt>Estimated tax</dt><dd>₱{{ \App\Services\Money::decimal($summary['tax_cents']) }}</dd></div><div><dt>Shipping</dt><dd>₱{{ \App\Services\Money::decimal($summary['shipping_cents']) }}</dd></div><div class="bag-grand-total"><dt>Estimated total</dt><dd>₱{{ \App\Services\Money::decimal($summary['total_cents']) }}</dd></div></dl><p class="bag-summary-help">Coupon discounts are applied at checkout.</p><div class="bag-payment-note"><span aria-hidden="true">✓</span><div><strong>Choose how to pay</strong><p>Cash on delivery or a preferred digital payment method.</p></div></div>
@if(auth()->check() && auth()->user()->role === 'customer' && count($summary['items']))
<form method="post" action="{{ route('orders.store') }}" class="bag-checkout">@csrf<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key',(string)\Illuminate\Support\Str::uuid()) }}"><label for="shipping_address" class="form-label">Delivery address</label><textarea @class(['form-control mb-2', 'is-invalid' => $errors->has('shipping_address')]) id="shipping_address" name="shipping_address" minlength="10" maxlength="2000" placeholder="House number, street, barangay, city, and postal code" required>{{ old('shipping_address') }}</textarea>@error('shipping_address')<div class="invalid-feedback mb-3">{{ $message }}</div>@enderror<label class="form-label mt-3" for="coupon_code">Coupon code <span class="text-muted fw-normal">(optional)</span></label><input @class(['form-control mb-3', 'is-invalid' => $errors->has('coupon_code')]) id="coupon_code" name="coupon_code" maxlength="40" placeholder="Enter your code" value="{{ old('coupon_code') }}">@error('coupon_code')<div class="invalid-feedback mb-3">{{ $message }}</div>@enderror<label class="form-label" for="payment_method">Payment method</label><select class="form-select mb-2" id="payment_method" name="payment_method" aria-describedby="payment-help">
@foreach(['cod'=>'Cash on delivery','gcash'=>'GCash','maya'=>'Maya (PayMaya)','credit_card'=>'Credit card'] as $method => $label)
<option value="{{ $method }}" @selected(old('payment_method','cod') === $method)>{{ $label }}</option>
@endforeach
</select><p id="payment-help" class="bag-summary-help">Digital methods record your preference only. Online payment processing is not yet available, and you will not be charged here.</p>
@error('payment_method')<p class="text-danger small">{{ $message }}</p>@enderror<button class="btn btn-primary w-100 bag-order-button" type="submit" @disabled(collect($summary['items'])->contains(fn($item)=>!$item['available']))>Place order <span aria-hidden="true">→</span></button></form>
@elseif(!auth()->check())
<a class="btn btn-primary w-100 mt-3" href="{{ route('login') }}">Sign in to checkout →</a>
@endif
</div></aside></div>
@endsection
