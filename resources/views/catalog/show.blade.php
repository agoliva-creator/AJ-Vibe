@extends('layout')
@section('content')
<a class="order-back-link" href="{{ route('catalog.index') }}">← Back to shop</a><div class="row g-4 mt-2 product-detail-layout"><div class="col-md-6"><div class="card overflow-hidden product-gallery-panel">
@if($product->image_path)
<img src="{{ app(\App\Services\ImageService::class)->url($product->image_path) }}" class="img-fluid product-detail-image" alt="{{ $product->name }}">
@else
<div class="product-placeholder" style="height:400px" aria-hidden="true">◇</div>
@endif
</div></div><div class="col-md-6 product-purchase-panel"><p class="eyebrow text-muted">{{ $product->category->name }}</p><h1>{{ $product->name }}</h1><p class="text-muted">Sold by {{ $product->vendor->name }}</p><p class="fs-3 fw-bold">₱{{ number_format($product->price,2) }}</p><p style="white-space:pre-line">{{ $product->description }}</p><p>{{ $product->stock }} available</p>
@if(!auth()->check() || auth()->user()->role === 'customer')
@if($product->variants->isNotEmpty())
<details class="card p-3 mb-3"><summary class="small fw-semibold">Size guide</summary><p class="small text-muted mt-3 mb-0">Measure your chest around the fullest point, your waist at its natural line, and your inseam from the inside leg to the ankle. Compare these measurements with the seller's description. S/M/L labels and shoe sizes vary by brand; confirm the listed measurements before ordering.</p></details>
<label class="form-label" for="product-variant">Size / Color</label><select class="form-select mb-3" id="product-variant" name="variant_id" form="product-cart-form" required><option value="">Choose your size and color</option>@foreach($product->variants->where('active',true) as $variant)<option value="{{ $variant->id }}" @disabled(!$variant->stock) @selected(old('variant_id') == $variant->id)>{{ $variant->label() }} · {{ $variant->stock }} available</option>@endforeach</select><p class="small text-muted">Check the seller's product description for measurements. Size labels vary between brands.</p>
@endif
<form class="product-purchase-form" id="product-cart-form" method="post" action="{{ route('cart.set') }}">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><label class="form-label" for="quantity">Quantity</label><input class="form-control mb-3" style="max-width:130px" name="quantity" id="quantity" type="number" min="1" max="{{ min(1000,$product->stock) }}" value="1" required><button class="btn btn-primary" @disabled(!$product->stock)>Add to cart</button></form>
@endif
</div></div>
<section class="card p-4 mt-5 product-reviews-panel"><h2 class="h4">Customer reviews</h2>
@forelse($reviews as $review)
<div class="border-bottom py-3"><strong>{{ $review->user->name }}</strong> · {{ $review->rating }}/5<p class="mb-2">{{ $review->body }}</p>
@can('delete',$review)
<form method="post" action="{{ route('reviews.destroy',$review) }}" data-confirm="Delete this review?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
@endcan
</div>
@empty
<p class="text-muted">No reviews yet.</p>
@endforelse
<div class="mt-3">{{ $reviews->links() }}</div>
@can('create',[\App\Models\Review::class,$product->id])
<form method="post" action="{{ route('reviews.store',$product) }}" class="mt-4">@csrf<h3 class="h5">Write or update your review</h3><label class="form-label" for="rating">Rating</label><select class="form-select mb-3" id="rating" name="rating">
@for($rating=5;$rating>=1;$rating--)
<option value="{{ $rating }}">{{ $rating }} / 5</option>
@endfor
</select><label class="form-label" for="body">Your experience</label><textarea class="form-control mb-3" id="body" name="body" minlength="5" maxlength="2000" required>{{ old('body',$myReview?->body) }}</textarea><button class="btn btn-primary">Save review</button></form>
@endcan
</section>
@endsection
