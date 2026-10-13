@extends('layout')
@section('title', 'Categories · AJ Vibe')
@section('content')
<div class="orders-heading"><div><p class="fashion-kicker mb-2">AJ Vibe / Catalog management</p><h1>Categories.</h1><p>Organize your collection and see what's inside.</p></div><a class="btn btn-primary" href="{{ route('records.create','categories') }}">+ Add category</a></div>
<div class="category-collection-list">
@forelse($records as $category)
<article class="category-collection-row"><a class="category-cover" href="{{ route('records.show',['categories',$category->id]) }}" aria-label="View {{ $category->name }}">
@php($cover = $category->products->first(fn($product) => $product->image_path))
@if($cover)
<img src="{{ app(\App\Services\ImageService::class)->url($cover->image_path) }}" alt="{{ $cover->name }}" loading="lazy">
@else
<span class="category-photo-fallback">Your collection starts here</span>
@endif
<span class="category-cover-number">{{ str_pad((string)(($records->firstItem() ?? 1)+$loop->index),2,'0',STR_PAD_LEFT) }}</span></a><div class="category-row-content"><p class="fashion-kicker">AJ Vibe / Collection</p><div class="category-row-title"><h2>{{ $category->name }}</h2><span>{{ $category->products_count }} products</span></div><p class="category-row-description">{{ $category->description ?: 'Build this collection with your next everyday favorites.' }}</p><div class="category-mini-previews">
@forelse($category->products as $product)
@if($product->image_path)
<a href="{{ route('records.show',['products',$product->id]) }}" title="{{ $product->name }}"><img src="{{ app(\App\Services\ImageService::class)->url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy"></a>
@else
<span class="category-photo-fallback">No image</span>
@endif
@empty
<span class="category-photo-fallback">No products yet</span>
@endforelse
</div><div class="category-row-actions"><a class="fashion-button fashion-button-small" href="{{ route('records.show',['categories',$category->id]) }}">Explore products ↗</a><a class="btn btn-sm btn-outline-secondary" href="{{ route('records.edit',['categories',$category->id]) }}">Edit category</a>@can('delete',$category)<form method="post" action="{{ route('records.destroy',['categories',$category->id]) }}" data-confirm="Delete this category?">@csrf @method('DELETE')<button class="category-delete-action">Delete</button></form>@endcan</div></div></article>
@empty
<div class="col-12"><div class="card p-5 text-center text-muted">No categories yet. Add your first category to organize your products.</div></div>
@endforelse
</div><div class="mt-4">{{ $records->links() }}</div>
@endsection
