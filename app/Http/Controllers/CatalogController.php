<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogRequest;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(CatalogRequest $request): mixed
    {
        $data = $request->validated();
        $query = Product::available()->with(['category', 'vendor', 'variants'])->withAvg('reviews', 'rating');
        if (! empty($data['search'])) {
            $query->where('name', 'like', '%'.$data['search'].'%');
        }
        if (! empty($data['category'])) {
            $query->where('category_id', $data['category']);
        }
        $products = $query->fashionOrder()->paginate($data['per_page'] ?? 12)->withQueryString();
        if ($request->is('api/*')) {
            return ProductResource::collection($products);
        }

        return view('catalog.index', ['products' => $products, 'categories' => Category::orderBy('name')->get()]);
    }

    public function show(Request $request, int $product): mixed
    {
        $product = Product::available()->with(['category', 'vendor', 'variants'])->withAvg('reviews', 'rating')->findOrFail($product);
        if ($request->is('api/*')) {
            return new ProductResource($product);
        }

        $reviews = $product->reviews()->with('user')->latest()->orderByDesc('id')->paginate(10);
        $myReview = $request->user() ? $product->reviews()->where('user_id', $request->user()->id)->first() : null;

        return view('catalog.show', compact('product', 'reviews', 'myReview'));
    }

    public function inventory(int $product): mixed
    {
        $product = Product::available()->with('variants')->findOrFail($product);

        $variants = $product->variants->where('active', true);

        return response()->json(['data' => ['product_id' => $product->id, 'stock' => $product->stock, 'available' => $product->variants->isEmpty() ? $product->stock > 0 : $variants->contains(fn ($variant) => $variant->stock > 0), 'variants' => $variants->map(fn ($variant) => ['id' => $variant->id, 'size' => $variant->size, 'color' => $variant->color, 'stock' => $variant->stock])->values()]]);
    }
}
