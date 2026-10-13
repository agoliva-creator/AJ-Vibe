<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Product $product): mixed
    {
        Gate::authorize('create', [Review::class, $product->id]);
        Review::updateOrCreate(['user_id' => $request->user()->id, 'product_id' => $product->id], $request->validated());

        return back()->with('success', 'Your review was saved.');
    }

    public function destroy(Request $request, Review $review): mixed
    {
        Gate::authorize('delete', $review);
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
