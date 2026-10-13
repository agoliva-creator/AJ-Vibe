@extends('layout')
@section('title', 'Your collection · AJ Vibe')
@section('body-class', 'fashion-dashboard')
@section('main-class', 'fashion-main')
@section('navigation')
@include('partials.fashion-navigation')
@endsection
@section('content')
<section class="fashion-hero" aria-labelledby="collection-title">
<img class="fashion-hero-photo" src="{{ asset('images/dashboard/bieber.jpg') }}" alt="New-season fashion collection" fetchpriority="high">
<div class="fashion-container fashion-hero-inner"><div class="fashion-hero-copy"><p class="fashion-kicker">The everyday edit / 2026</p><h1 id="collection-title">Brand New<br>Collection</h1><p class="fashion-hero-description">Thoughtful essentials. Fresh perspectives.<br>Find your next favorite from our independent sellers.</p><div class="fashion-hero-points"><span>✓ Independent sellers</span><span>✓ Fresh arrivals</span><span>✓ Easy shopping</span></div><a class="fashion-button" href="{{ route('catalog.index') }}">Explore shop <span aria-hidden="true">↗</span></a></div></div>
<div class="fashion-hero-caption">A new look for every day.</div>
</section>
<section class="fashion-benefits" aria-label="Shopping features"><div class="fashion-container fashion-benefit-grid">
<div class="fashion-benefit"><svg viewBox="0 0 32 32" aria-hidden="true"><rect x="3" y="6" width="23" height="17" rx="2"/><path d="M3 11h23M7 17h7"/><circle cx="24" cy="24" r="6"/><path d="m21 24 2 2 4-4"/></svg><h2>Payment on delivery</h2><p>Order now, pay on arrival</p></div>
<div class="fashion-benefit"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M7 10a11 11 0 1 1-2 12M7 3v7H0"/><path d="M16 8v9l6 3"/></svg><h2>Track your orders</h2><p>Follow every step to delivery</p></div>
<div class="fashion-benefit"><svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="9" r="5"/><path d="M6 29v-4a10 10 0 0 1 20 0v4"/></svg><h2>Your own space</h2><p>Manage your profile and purchases</p></div>
<div class="fashion-benefit"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M5 12h22v16H5zM3 12l3-8h20l3 8M11 28V18h10v10"/><path d="M3 12a4 4 0 0 0 7 2 4 4 0 0 0 6 0 4 4 0 0 0 6 0 4 4 0 0 0 7-2"/></svg><h2>Independent sellers</h2><p>Discover something different</p></div>
</div></section>
<section class="fashion-arrivals fashion-container" aria-labelledby="arrivals-title">
<div class="fashion-section-heading"><div><p>Check out the latest products</p><h2 id="arrivals-title">New Arrivals</h2></div><a class="fashion-outline-button" href="{{ route('catalog.index') }}">View all <span aria-hidden="true">↗</span></a></div>
<div class="fashion-product-grid">
@forelse($arrivals as $product)
<article class="fashion-product"><a class="fashion-product-visual" href="{{ route('catalog.show',$product) }}">
@if($product->image_path)
<img src="{{ app(\App\Services\ImageService::class)->url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy">
@else
<div class="fashion-product-fallback" aria-hidden="true"><svg viewBox="0 0 120 140"><path d="M22 44h76l6 78H16z"/><path d="M40 48V32a20 20 0 0 1 40 0v16"/><path d="M28 112h64"/></svg><span>{{ $product->category->name }}</span></div>
@endif
<span class="fashion-product-tag">{{ $product->stock ? 'NEW' : 'SOLD OUT' }}</span></a><div class="fashion-product-info">
@if($product->reviews_avg_rating)
<div class="fashion-rating"><span aria-hidden="true">★</span> {{ number_format($product->reviews_avg_rating,1) }} / 5</div>
@else
<p class="fashion-product-category">{{ $product->category->name }}</p>
@endif
<h3><a href="{{ route('catalog.show',$product) }}">{{ $product->name }}</a></h3><p class="fashion-price">₱{{ number_format($product->price,2) }}</p>@if($product->variants->isNotEmpty())<a class="fashion-button fashion-button-small" href="{{ route('catalog.show',$product) }}">Add to cart +</a>@else<form method="post" action="{{ route('cart.set') }}">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><input type="hidden" name="quantity" value="1"><button class="fashion-button fashion-button-small" @disabled(!$product->stock)>{{ $product->stock ? 'Add to cart' : 'Out of stock' }} <span aria-hidden="true">+</span></button></form>@endif</div></article>
@empty
<div class="fashion-empty"><h3>Your next favorite is on its way.</h3><p>Our sellers are preparing their latest arrivals. Check back soon.</p><a href="{{ route('catalog.index') }}">Browse the shop →</a></div>
@endforelse
</div></section>
<section class="fashion-editorial" aria-label="Explore your collection"><div class="fashion-container fashion-editorial-grid">
<a class="fashion-editorial-card" href="{{ route('catalog.index') }}"><img src="{{ asset('images/dashboard/collection-editorial.jpg') }}" alt="Explore the fashion collection" loading="lazy"><div><span>The collection</span><h2>Find your style.</h2><span class="fashion-editorial-link">Shop the edit ↗</span></div></a>
<a class="fashion-editorial-card fashion-editorial-orders" href="{{ route('orders.index') }}"><img src="{{ asset('images/dashboard/orders-editorial.jpg') }}" alt="Casual outfit on a forest stairway" loading="lazy"><div><span>Your purchases</span><h2>Every order.<br>All in one place.</h2><span class="fashion-editorial-link">View your orders ↗</span></div></a>
<a class="fashion-editorial-card fashion-editorial-account" href="{{ route('account') }}"><img src="{{ asset('images/dashboard/bieber.jpg') }}" alt="Your personal collection" loading="lazy"><div><span>Hello, {{ auth()->user()->name }}</span><h2>Make it yours.</h2><span class="fashion-editorial-link">Your account ↗</span></div></a>
</div></section>
@endsection
@section('footer')
@include('partials.fashion-footer')
@endsection
