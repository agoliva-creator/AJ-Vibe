<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(private PricingService $pricing) {}

    private function key(int $productId, ?int $variantId): string
    {
        return $variantId ? $productId.':'.$variantId : (string) $productId;
    }

    public function quantities(Request $request): array
    {
        if (! $request->user()) {
            return $request->session()->get('cart', []);
        }
        $quantities = [];
        foreach ($request->user()->cartItems()->get() as $item) {
            $quantities[$this->key($item->product_id, $item->variant_id)] = $item->quantity;
        }

        return $quantities;
    }

    public function summary(Request $request): array
    {
        $quantities = $this->quantities($request);
        $ids = array_map(fn ($key) => (int) explode(':', (string) $key)[0], array_keys($quantities));
        $products = Product::withTrashed()->with(['category', 'vendor', 'variants'])->whereIn('id', $ids)->get()->keyBy('id');
        $items = [];
        $subtotal = 0;
        foreach ($quantities as $key => $quantity) {
            [$id,$variantId] = array_pad(explode(':', (string) $key), 2, null);
            $product = $products->get((int) $id);
            if (! $product) {
                continue;
            }$variant = $variantId ? $product->variants->firstWhere('id', (int) $variantId) : null;
            $line = Money::cents($product->price) * $quantity;
            $subtotal += $line;
            $available = ! $product->trashed() && $product->status === 'active' && $product->vendor?->active && $product->vendor?->role === 'vendor' && ($variantId ? ($variant?->active && $variant->stock >= $quantity) : ($product->variants->isEmpty() && $product->stock >= $quantity));
            $items[] = ['product' => $product, 'variant' => $variant, 'variant_id' => $variantId ? (int) $variantId : null, 'quantity' => (int) $quantity, 'line_total_cents' => $line, 'available' => $available];
        }

        return ['items' => $items] + $this->pricing->totals($subtotal);
    }

    private function validateQuantity(int $id, int $quantity, ?int $variantId): void
    {
        if (! $quantity) {
            return;
        }$product = Product::available()->with('variants')->find($id);
        $variant = $variantId ? $product?->variants->firstWhere('id', $variantId) : null;
        if (! $product || ($variantId ? (! $variant || ! $variant->active || $variant->stock < $quantity) : ($product->variants->isNotEmpty() || $product->stock < $quantity))) {
            throw ValidationException::withMessages(['quantity' => 'Select an available size/color with sufficient stock.']);
        }
    }

    public function set(Request $request, int $productId, int $quantity, ?int $variantId = null): void
    {
        if ($request->user()) {
            DB::transaction(function () use ($request, $productId, $quantity, $variantId): void {
                User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $this->validateQuantity($productId, $quantity, $variantId);
                $key = ['user_id' => $request->user()->id, 'product_id' => $productId, 'variant_key' => $variantId ? (string) $variantId : 'base'];
                $current = CartItem::where($key)->first();
                if (! $quantity) {
                    CartItem::where($key)->delete();

                    return;
                }abort_if(! $current && $request->user()->cartItems()->count() >= 100, 422, 'Cart limit reached.');
                $previous = $current?->quantity ?? 0;
                CartItem::updateOrCreate($key, ['quantity' => $quantity, 'variant_id' => $variantId]);
                if ($quantity > $previous) {
                    app(ActivityService::class)->cart(Product::findOrFail($productId));
                }
            }, 3);

            return;
        }
        $this->validateQuantity($productId, $quantity, $variantId);
        $cart = $this->quantities($request);
        $key = $this->key($productId, $variantId);
        $previous = $cart[$key] ?? 0;
        if ($quantity) {
            $cart[$key] = $quantity;
        } else {
            unset($cart[$key]);
        }if (count($cart) > 100) {
            throw ValidationException::withMessages(['cart' => 'A cart may contain at most 100 items.']);
        }$request->session()->put('cart', $cart);
        if ($quantity > $previous) {
            app(ActivityService::class)->cart(Product::findOrFail($productId));
        }
    }

    public function sync(User $user, array $items): void
    {
        DB::transaction(function () use ($user, $items): void {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $seen = [];
            $previous = [];
            foreach ($user->cartItems()->get() as $line) {
                $previous[$this->key($line->product_id, $line->variant_id)] = $line->quantity;
            }
            foreach ($items as $item) {
                $key = $this->key($item['product_id'], $item['variant_id'] ?? null);
                if (isset($seen[$key])) {
                    throw ValidationException::withMessages(['items' => 'Duplicate product and variant.']);
                }$seen[$key] = true;
                $this->validateQuantity($item['product_id'], $item['quantity'], $item['variant_id'] ?? null);
            }
            $user->cartItems()->delete();
            foreach ($items as $item) {
                $variantId = $item['variant_id'] ?? null;
                $user->cartItems()->create($item + ['variant_key' => $variantId ? (string) $variantId : 'base']);
                if ($item['quantity'] > ($previous[$this->key($item['product_id'], $variantId)] ?? 0)) {
                    app(ActivityService::class)->cart(Product::findOrFail($item['product_id']));
                }
            }
        }, 3);
    }

    public function changeVariant(Request $request, int $productId, int $originalVariantId, ?int $variantId, int $quantity): void
    {
        $change = function () use ($request, $productId, $originalVariantId, $variantId, $quantity): void {
            if ($request->user()) {
                User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            }
            $cart = $this->quantities($request);
            $source = $this->key($productId, $originalVariantId);
            $target = $this->key($productId, $variantId);
            if (! isset($cart[$source])) {
                throw ValidationException::withMessages(['variant_id' => 'This cart item no longer exists. Refresh your cart.']);
            }
            $combined = $quantity ? $quantity + ($cart[$target] ?? 0) : 0;
            if ($combined > 1000) {
                throw ValidationException::withMessages(['quantity' => 'The combined quantity exceeds the cart limit.']);
            }
            if ($quantity) {
                $this->set($request, $productId, $combined, $variantId);
            }
            $this->set($request, $productId, 0, $originalVariantId);
        };
        if ($request->user()) {
            DB::transaction($change, 3);
        } else {
            $change();
        }
    }

    public function mergeGuest(Request $request): void
    {
        $cart = $request->session()->get('cart', []);
        DB::transaction(function () use ($request, $cart): void {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            foreach ($cart as $key => $quantity) {
                [$id,$variantId] = array_pad(explode(':', (string) $key), 2, null);
                $variantId = $variantId ? (int) $variantId : null;
                $product = Product::available()->with('variants')->find((int) $id);
                if (! $product) {
                    continue;
                }$variant = $variantId ? $product->variants->firstWhere('id', $variantId) : null;
                $stock = $variantId ? ($variant?->active ? $variant->stock : 0) : ($product->variants->isEmpty() ? $product->stock : 0);
                if (! $stock) {
                    continue;
                }$criteria = ['user_id' => $user->id, 'product_id' => $product->id, 'variant_key' => $variantId ? (string) $variantId : 'base'];
                $current = CartItem::where($criteria)->first();
                if (! $current && $user->cartItems()->count() >= 100) {
                    continue;
                }CartItem::updateOrCreate($criteria, ['variant_id' => $variantId, 'quantity' => min(1000, $stock, $quantity + ($current?->quantity ?? 0))]);
            }
        }, 3);
        $request->session()->forget('cart');
    }
}
