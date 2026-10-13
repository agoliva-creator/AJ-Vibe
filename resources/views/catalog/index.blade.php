@extends('layout')
@section('content')
<section class="mens-catalog-hero mb-5"><div><p class="eyebrow">AJ Vibe / Men's collection</p><h1>Everyday style.<br>Made for you.</h1><p>Build your rotation with easy tees, statement jackets,<br class="d-none d-md-block"> fresh sneakers, and everyday essentials.</p><a href="#collection" class="fashion-button">Explore menswear ↗</a></div><img src="{{ asset('images/dashboard/bieber.jpg') }}" alt="Men's fashion collection" fetchpriority="high"><span class="mens-hero-caption">THE EVERYDAY EDIT / AJ VIBE</span></section>
<div class="fashion-section-heading" id="collection"><div><p>Your wardrobe starts here</p><h2>The Men's Edit</h2></div><span class="catalog-count">{{ $products->total() }} products</span></div>
<form class="row g-3 mb-4 catalog-filter-form" method="get" action="{{ route('catalog.index') }}"><div class="col-md-6"><label class="visually-hidden" for="search">Search products</label><input class="form-control" id="search" name="search" placeholder="Search products" value="{{ request('search') }}"></div><div class="col-md-4"><label class="visually-hidden" for="category">Category</label><select class="form-select" name="category" id="category"><option value="">All categories</option>
@foreach($categories as $category)
<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
@endforeach
</select></div><div class="col-md-2"><button class="btn btn-primary w-100">Find products</button></div></form>
<div class="row g-4">
@forelse($products as $product)
<div class="col-6 col-lg-3"><article class="card catalog-product-card h-100 overflow-hidden">
<a class="catalog-product-visual" href="{{ route('catalog.show',$product) }}" aria-label="View {{ $product->name }}">
<span class="catalog-stock-label">{{ $product->stock ? 'Available' : 'Sold out' }}</span>
@if($product->image_path)
<img class="product-image" src="{{ app(\App\Services\ImageService::class)->url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy">
@else
<div class="product-placeholder" aria-hidden="true">◇</div>
@endif
</a>
<div class="card-body"><span class="eyebrow text-muted">{{ $product->category->name }}</span><h2 class="h5 mt-2"><a class="text-decoration-none" href="{{ route('catalog.show',$product) }}">{{ $product->name }}</a></h2><p class="small text-muted">By {{ $product->vendor->name }} · {{ $product->stock ? $product->stock.' in stock' : 'Out of stock' }}</p><div class="d-flex justify-content-between align-items-center"><strong class="fs-5">₱{{ number_format($product->price,2) }}</strong><a class="btn btn-outline-success btn-sm" href="{{ route('catalog.show',$product) }}">View product</a></div></div></article></div>
@empty
<div class="col-12"><div class="card p-5 text-center"><h2 class="h4">No products found</h2><p class="text-muted mb-0">Try another search or check back soon.</p></div></div>
@endforelse
</div><div class="mt-4">{{ $products->links() }}</div>
@endsection
