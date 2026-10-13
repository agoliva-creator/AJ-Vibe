<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Http\Requests\CartSyncRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function show(Request $request): mixed
    {
        $summary = $this->cart->summary($request);

        return $request->is('api/*') ? new CartResource($summary) : view('cart', compact('summary'));
    }

    public function set(CartRequest $request): mixed
    {
        abort_if($request->user() && $request->user()->role !== 'customer', 403);
        $data = $request->validated();
        if (! empty($data['original_variant_id']) && ($data['variant_id'] ?? null) !== $data['original_variant_id']) {
            $this->cart->changeVariant($request, $data['product_id'], $data['original_variant_id'], $data['variant_id'] ?? null, $data['quantity']);
        } else {
            $this->cart->set($request, $data['product_id'], $data['quantity'], $data['variant_id'] ?? null);
        }

        return $request->is('api/*') ? new CartResource($this->cart->summary($request)) : redirect()->route('cart.show')->with('success', 'Cart updated.');
    }

    public function sync(CartSyncRequest $request): mixed
    {
        $this->cart->sync($request->user(), $request->validated('items'));

        return new CartResource($this->cart->summary($request));
    }
}
