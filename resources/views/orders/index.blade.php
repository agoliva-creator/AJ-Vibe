@extends('layout')
@section('title', 'Orders · AJ Vibe')
@section('content')
<div class="orders-heading"><div><p class="fashion-kicker mb-2">AJ Vibe / {{ auth()->user()->role === 'customer' ? 'Your purchases' : 'Order management' }}</p><h1>{{ auth()->user()->isVendor() ? 'Your sales orders.' : 'Your orders.' }}</h1><p>Follow every purchase, from the first click to your doorstep.</p></div><a class="fashion-outline-button" href="{{ auth()->user()->role === 'customer' ? route('catalog.index') : route('dashboard') }}">{{ auth()->user()->role === 'customer' ? 'Explore the collection' : 'Back to dashboard' }} ↗</a></div>
<div class="orders-list-header"><h2>{{ auth()->user()->role === 'customer' ? 'Order history' : 'All orders' }}</h2><span>{{ $orders->total() }} {{ $orders->total() === 1 ? 'order' : 'orders' }}</span></div>
<div class="orders-list">
@forelse($orders as $order)
<article class="card order-history-card"><div class="order-card-top"><div><span class="order-card-label">Order reference</span><h3>#{{ $order->id }}</h3></div><span class="workspace-status workspace-status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></div><div class="order-card-body"><div class="order-card-products"><p class="small mb-3"><span class="order-card-label">Customer</span><br><strong>{{ $order->buyer_name }}</strong></p><span class="order-card-label">{{ auth()->user()->isVendor() ? 'Your items' : 'Items in this order' }}</span><div class="order-mini-products">
@foreach($order->items->take(2) as $item)
<div class="order-mini-product">@if($item->product?->image_path)<img class="order-product-thumbnail" src="{{ app(\App\Services\ImageService::class)->url($item->product->image_path) }}" alt="{{ $item->product_name }}{{ $item->variant_label ? ' ('.$item->variant_label.')' : '' }}" loading="lazy">@else<span class="order-product-thumbnail order-photo-placeholder" aria-hidden="true">◇</span>@endif<span>{{ $item->product_name }}{{ $item->variant_label ? ' ('.$item->variant_label.')' : '' }}</span></div>
@endforeach
@if($order->items->count() > 2)<span class="order-item-count">+{{ $order->items->count() - 2 }} more</span>@endif
</div><span class="order-item-count">{{ $order->items->sum('quantity') }} items</span></div><div><span class="order-card-label">Placed on</span><p>{{ $order->created_at->copy()->timezone(config('commerce.display_timezone', 'Asia/Manila'))->format('M j, Y') }}</p><span class="order-item-count">{{ $order->created_at->copy()->timezone(config('commerce.display_timezone', 'Asia/Manila'))->format('g:i A') }}</span></div><div><span class="order-card-label">{{ auth()->user()->isVendor() ? 'Your items subtotal' : 'Order total' }}</span><p class="order-card-total">₱{{ \App\Services\Money::decimal(auth()->user()->isVendor() ? $order->items->sum(fn($item)=>$item->quantity*$item->unit_price_cents) : $order->total_cents) }}</p></div><a class="btn btn-outline-secondary order-view-button" href="{{ route('orders.show',$order) }}">View order ↗</a></div></article>
@empty
<div class="card orders-empty"><svg viewBox="0 0 64 72" aria-hidden="true"><path d="M10 22h44v44H10z"/><path d="M22 26V16a10 10 0 0 1 20 0v10M22 40h20M22 49h14"/></svg><h2>No orders yet.</h2><p>{{ auth()->user()->role === 'customer' ? 'Your next favorite is waiting. Your purchases will appear here.' : 'New orders will appear here when customers place a purchase.' }}</p><a class="fashion-button" href="{{ route('catalog.index') }}">Explore the shop ↗</a></div>
@endforelse
</div><div class="mt-4">{{ $orders->links() }}</div>
@endsection
