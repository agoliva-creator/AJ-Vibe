@extends('layout')
@section('title', 'Order #'.$order->id.' · AJ Vibe')
@section('content')
<a class="order-back-link" href="{{ route('orders.index') }}">← Back to orders</a><div class="order-detail-heading"><div><p class="fashion-kicker mb-2">AJ Vibe / Order details</p><h1>Order #{{ $order->id }}</h1><p class="text-muted small mb-0">Placed {{ $order->created_at->copy()->timezone(config('commerce.display_timezone', 'Asia/Manila'))->format('F j, Y · g:i A') }}</p></div><span class="workspace-status workspace-status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></div><div class="order-detail-layout"><section class="card order-detail-panel"><div class="order-items-heading"><h2>Items & fulfillment</h2><span>{{ $order->items->sum('quantity') }} items</span></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Product</th><th>Quantity</th><th>Unit price</th><th>Fulfillment</th><th></th></tr></thead><tbody>
@foreach($order->items as $item)
<tr><td class="fw-semibold"><div class="order-mini-product">@if($item->product?->image_path)<img class="order-product-thumbnail" src="{{ app(\App\Services\ImageService::class)->url($item->product->image_path) }}" alt="{{ $item->product_name }}{{ $item->variant_label ? ' ('.$item->variant_label.')' : '' }}" loading="lazy">@else<span class="order-product-thumbnail order-photo-placeholder" aria-hidden="true">◇</span>@endif<span>{{ $item->product_name }}{{ $item->variant_label ? ' ('.$item->variant_label.')' : '' }}</span></div></td><td>{{ $item->quantity }}</td><td class="text-nowrap">₱{{ \App\Services\Money::decimal($item->unit_price_cents) }}</td><td><span class="workspace-status workspace-status-{{ $item->status }}">{{ ucfirst($item->status) }}</span></td><td>
@can('update',$order)
@if(in_array($item->status,['pending','processing','shipped']) && $order->status !== 'cancelled')
<form method="post" action="{{ route('orders.update',$order) }}">@csrf @method('PATCH')<input type="hidden" name="item_id" value="{{ $item->id }}"><input type="hidden" name="status" value="{{ ['pending'=>'processing','processing'=>'shipped','shipped'=>'delivered'][$item->status] }}"><button class="btn btn-sm btn-outline-success">{{ ['pending'=>'Start processing','processing'=>'Mark shipped','shipped'=>'Mark delivered'][$item->status] }}</button></form>
@endif
@endcan
</td></tr>
@endforeach
</tbody></table></div><section class="mt-4"><h2 class="h5 mb-3">Shipment tracking</h2>
@foreach($order->items as $shipment)
<div class="border-top py-3"><p class="small fw-semibold mb-2">{{ $shipment->product_name }}{{ $shipment->variant_label ? ' ('.$shipment->variant_label.')' : '' }}</p>
@if($shipment->tracking_number)<p class="small mb-2">{{ $shipment->courier }} · <strong>{{ $shipment->tracking_number }}</strong></p>@else<p class="small text-muted mb-2">Tracking details will appear when your seller updates the shipment.</p>@endif
@can('update',$order)
@if($shipment->status !== 'cancelled')
<form method="post" action="{{ route('orders.tracking',$order) }}" class="d-flex flex-wrap gap-2">@csrf @method('PATCH')<input type="hidden" name="item_id" value="{{ $shipment->id }}"><div><label class="visually-hidden" for="courier-{{ $shipment->id }}">Courier for {{ $shipment->product_name }}</label><input class="form-control" id="courier-{{ $shipment->id }}" name="courier" placeholder="Courier (e.g. J&T)" value="{{ $shipment->courier }}" maxlength="80" required></div><div><label class="visually-hidden" for="tracking-{{ $shipment->id }}">Tracking number for {{ $shipment->product_name }}</label><input class="form-control" id="tracking-{{ $shipment->id }}" name="tracking_number" placeholder="Tracking number" value="{{ $shipment->tracking_number }}" maxlength="100" required></div><button class="btn btn-outline-secondary">Save tracking</button></form>
@endif
@endcan
</div>
@endforeach
</section></section><aside class="order-detail-sidebar"><section class="card order-delivery-card"><section class="order-delivery-details"><span class="order-card-label">Delivery information</span><h2 class="h5 mt-2">{{ $order->buyer_name }}</h2><p class="mb-0">{{ $order->shipping_address }}</p></section></section><section class="card order-detail-summary"><h2 class="h5">Order summary</h2>
@if(auth()->user()->isVendor())<p class="small text-muted">Your items subtotal</p><strong class="fs-4">₱{{ \App\Services\Money::decimal($order->items->sum(fn($item)=>$item->quantity*$item->unit_price_cents)) }}</strong>@endif
@if(!auth()->user()->isVendor())
<div class="bag-payment-note"><div><strong>Payment method: {{ ['cod'=>'Cash on delivery','gcash'=>'GCash','maya'=>'Maya (PayMaya)','credit_card'=>'Credit card'][$order->payment_method] ?? 'Cash on delivery' }}</strong><p>{{ $order->payment_method === 'cod' ? 'Payment is due on delivery.' : 'Awaiting payment. Online payment processing is not yet available; this order has not been charged.' }}</p></div></div>
<dl class="order-detail-totals"><div><dt>Subtotal</dt><dd>₱{{ \App\Services\Money::decimal($order->subtotal_cents) }}</dd></div><div><dt>Discount</dt><dd>−₱{{ \App\Services\Money::decimal($order->discount_cents) }}</dd></div><div><dt>Tax</dt><dd>₱{{ \App\Services\Money::decimal($order->tax_cents) }}</dd></div><div><dt>Shipping</dt><dd>₱{{ \App\Services\Money::decimal($order->shipping_cents) }}</dd></div><div class="order-detail-total"><dt>Total</dt><dd>₱{{ \App\Services\Money::decimal($order->total_cents) }}</dd></div></dl><div class="order-document-actions"><a class="btn btn-primary" href="{{ route('orders.invoice',$order) }}">Print invoice</a><a class="btn btn-outline-success" href="{{ route('orders.pdf',$order) }}">Download PDF</a><form method="post" action="{{ route('orders.email',$order) }}">@csrf<button class="btn btn-outline-secondary">Email confirmation</button></form></div>
@endif
@can('cancel',$order)
@if(in_array($order->status,['pending','processing']))
<form class="mt-3" method="post" action="{{ route('orders.cancel',$order) }}" data-confirm="Cancel this order and restore its inventory?">@csrf<button class="btn btn-outline-danger">Cancel order</button></form>
@endif
@endcan
@can('delete',$order)
<form class="mt-3" method="post" action="{{ route('orders.destroy',$order) }}" data-confirm="Remove this cancelled order from the active list?">@csrf @method('DELETE')<button class="btn btn-outline-danger">Delete cancelled order</button></form>
@endcan
</section></aside></div>
@endsection
