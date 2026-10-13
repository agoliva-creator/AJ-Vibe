@extends('layout')
@section('title', $record->name.' · AJ Vibe')
@section('content')
<a class="order-back-link" href="{{ route('records.index','categories') }}">← Back to categories</a><div class="orders-heading"><div><p class="fashion-kicker mb-2">Category / {{ $products->total() }} products</p><h1>{{ $record->name }}</h1><p>{{ $record->description }}</p></div><a class="btn btn-primary" href="{{ route('records.edit',['categories',$record->id]) }}">Edit category</a></div>
<div class="row g-4">
@forelse($products as $product)
<div class="col-6 col-lg-3"><article class="card catalog-product-card h-100"><a class="catalog-product-visual" href="{{ route('records.show',['products',$product->id]) }}">@if($product->image_path)<img class="product-image" src="{{ app(\App\Services\ImageService::class)->url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy">@else<span class="category-photo-fallback">No image</span>@endif</a><div class="card-body"><h2 class="h6">{{ $product->name }}</h2><p class="small text-muted">{{ ucfirst($product->status) }} · {{ $product->stock }} in stock</p><strong>₱{{ number_format($product->price,2) }}</strong></div></article></div>
@empty
<div class="col-12"><div class="card p-5 text-center text-muted">No products in this category yet.</div></div>
@endforelse
</div><div class="mt-4">{{ $products->links() }}</div>
@endsection
