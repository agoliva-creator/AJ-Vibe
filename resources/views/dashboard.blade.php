@extends('layout')
@section('title', (auth()->user()->isAdmin() ? 'Admin' : 'Seller').' workspace · AJ Vibe')
@section('body-class', 'fashion-dashboard workspace-shell')
@section('main-class', 'fashion-main')
@section('content')
@if(auth()->user()->isAdmin())
@include('partials.admin-overview')
@else
<section class="fashion-hero workspace-hero" aria-labelledby="workspace-title">
<img class="fashion-hero-photo" src="{{ asset('images/dashboard/bieber.jpg') }}" alt="Fashion collection for your marketplace" fetchpriority="high">
<div class="fashion-container fashion-hero-inner"><div class="fashion-hero-copy"><p class="fashion-kicker">{{ auth()->user()->isAdmin() ? 'Admin' : 'Seller' }} workspace / AJ Vibe</p><h1 id="workspace-title">{{ auth()->user()->isAdmin() ? 'Your Store.' : 'Your Collection.' }}<br>{{ auth()->user()->isAdmin() ? 'One Clear View.' : 'New Possibilities.' }}</h1><p class="fashion-hero-description">Welcome back, {{ auth()->user()->name }}.<br>{{ auth()->user()->isAdmin() ? 'Keep your products, people, and orders moving together.' : 'Create your next favorite. Manage inventory and follow your sales.' }}</p><div class="fashion-hero-points"><span>✓ Live inventory</span><span>✓ Order tracking</span><span>✓ {{ auth()->user()->isAdmin() ? 'Full store control' : 'Your seller workspace' }}</span></div><a class="fashion-button" href="{{ route('records.create','products') }}">Add product <span aria-hidden="true">+</span></a><a class="workspace-hero-secondary" href="{{ route('orders.index') }}">View orders →</a></div></div>
</section>
<section class="fashion-benefits workspace-statistics" aria-label="Store statistics"><div class="fashion-container fashion-benefit-grid">
<a href="{{ route('records.index','products') }}"><span class="workspace-stat-label">{{ auth()->user()->isVendor() ? 'Your products' : 'Products' }}</span><strong>{{ $products }}</strong><span class="workspace-stat-detail">Manage the collection ↗</span></a>
<a href="{{ route('orders.index') }}"><span class="workspace-stat-label">{{ auth()->user()->isVendor() ? 'Your orders' : 'Orders' }}</span><strong>{{ $orders }}</strong><span class="workspace-stat-detail">Follow fulfillment ↗</span></a>
<a href="{{ route('orders.index') }}"><span class="workspace-stat-label">Delivered sales</span><strong class="workspace-stat-money">₱{{ \App\Services\Money::decimal((int)$sales) }}</strong><span class="workspace-stat-detail">Before discounts and tax</span></a>
<a href="{{ auth()->user()->isAdmin() ? route('records.index','users') : route('records.index','products') }}"><span class="workspace-stat-label">{{ auth()->user()->isAdmin() ? 'Registered users' : 'Low-stock products' }}</span><strong>{{ $extraCount }}</strong><span class="workspace-stat-detail">{{ auth()->user()->isAdmin() ? 'Manage your people ↗' : 'Review your inventory ↗' }}</span></a>
</div></section>
<section class="fashion-arrivals fashion-container" aria-labelledby="workspace-products">
<div class="fashion-section-heading"><div><p>{{ auth()->user()->isAdmin() ? 'The latest additions to your marketplace' : 'Your latest additions, all in one place' }}</p><h2 id="workspace-products">Your Collection</h2></div><a class="fashion-outline-button" href="{{ route('records.index','products') }}">Manage all ↗</a></div>
<div class="fashion-product-grid">
@forelse($collection as $product)
<article class="fashion-product"><a class="fashion-product-visual" href="{{ route('records.edit',['products',$product->id]) }}">
@if($product->image_path)
<img src="{{ app(\App\Services\ImageService::class)->url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy">
@else
<div class="fashion-product-fallback" aria-hidden="true"><svg viewBox="0 0 120 140"><path d="M22 44h76l6 78H16z"/><path d="M40 48V32a20 20 0 0 1 40 0v16"/><path d="M28 112h64"/></svg><span>{{ $product->category->name }}</span></div>
@endif
<span class="fashion-product-tag">{{ strtoupper($product->status) }}</span></a><div class="fashion-product-info"><p class="fashion-product-category">{{ $product->category->name }} · {{ $product->stock }} in stock</p><h3><a href="{{ route('records.show',['products',$product->id]) }}">{{ $product->name }}</a></h3><p class="fashion-price">₱{{ number_format($product->price,2) }}</p><a class="fashion-button fashion-button-small" href="{{ route('records.edit',['products',$product->id]) }}">Edit product ↗</a></div></article>
@empty
<div class="fashion-empty"><h3>Your collection starts here.</h3><p>Add your first product and bring your storefront to life.</p><a class="fashion-button" href="{{ route('records.create','products') }}">Add product +</a></div>
@endforelse
</div></section>
<section class="workspace-orders fashion-container" aria-labelledby="recent-orders">
<div class="fashion-section-heading"><div><p>Keep every purchase moving</p><h2 id="recent-orders">Recent Orders</h2></div><a class="fashion-outline-button" href="{{ route('orders.index') }}">View all ↗</a></div>
<div class="workspace-order-table table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Status</th><th>{{ auth()->user()->isVendor() ? 'Your items subtotal' : 'Total' }}</th><th></th></tr></thead><tbody>
@forelse($recentOrders as $order)
<tr><td class="fw-semibold">#{{ $order->id }}</td><td>{{ $order->buyer_name }}</td><td>{{ $order->created_at->copy()->timezone(config('commerce.display_timezone', 'Asia/Manila'))->format('M j, Y') }}</td><td><span class="workspace-status workspace-status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td><td>₱{{ \App\Services\Money::decimal(auth()->user()->isVendor() ? $order->items->sum(fn($item)=>$item->quantity*$item->unit_price_cents) : $order->total_cents) }}</td><td><a href="{{ route('orders.show',$order) }}">View →</a></td></tr>
@empty
<tr><td colspan="6" class="text-center text-muted py-5">Your recent orders will appear here.</td></tr>
@endforelse
</tbody></table></div></section>
<section class="fashion-editorial"><div class="fashion-container fashion-editorial-grid">
<a class="fashion-editorial-card" href="{{ route('records.index','products') }}"><img src="{{ asset('images/dashboard/collection-editorial.jpg') }}" alt="Manage your collection" loading="lazy"><div><span>The collection</span><h2>Make room<br>for what's next.</h2><span class="fashion-editorial-link">Manage products ↗</span></div></a>
<a class="fashion-editorial-card fashion-editorial-orders" href="{{ route('orders.index') }}"><img src="{{ asset('images/dashboard/orders-editorial.jpg') }}" alt="Casual outfit on a forest stairway" loading="lazy"><div><span>Every purchase matters</span><h2>From order<br>to delivery.</h2><span class="fashion-editorial-link">Process orders ↗</span></div></a>
<a class="fashion-editorial-card fashion-editorial-account" href="{{ auth()->user()->isAdmin() ? route('settings') : route('account') }}"><img src="{{ asset('images/dashboard/bieber.jpg') }}" alt="Build your store's identity" loading="lazy"><div><span>{{ auth()->user()->isAdmin() ? 'Your marketplace' : 'Your seller profile' }}</span><h2>Make it yours.</h2><span class="fashion-editorial-link">{{ auth()->user()->isAdmin() ? 'Store settings' : 'Your account' }} ↗</span></div></a>
</div></section>
@endif
@endsection
