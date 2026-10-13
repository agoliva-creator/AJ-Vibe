<?php

namespace App\Services;

use App\Mail\LowStockAlert;
use App\Mail\OrderConfirmation;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private PricingService $pricing) {}

    public function place(User $buyer, array $data): Order
    {
        $paymentMethod = $data['payment_method'] ?? 'cod';
        $fingerprint = ['shipping_address' => $data['shipping_address'], 'coupon_code' => $data['coupon_code'] ?? ''];
        if ($paymentMethod !== 'cod') {
            $fingerprint['payment_method'] = $paymentMethod;
        }
        $hash = hash('sha256', json_encode($fingerprint));

        return DB::transaction(function () use ($buyer, $data, $hash, $paymentMethod): Order {
            $user = User::whereKey($buyer->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->active && $user->role === 'customer', 403);
            $existing = Order::withTrashed()->where('user_id', $user->id)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                abort_if($existing->trashed(), 409, 'This order was archived. Start a new checkout.');
                abort_unless(hash_equals($existing->request_hash, $hash), 409, 'This checkout key was already used with different details.');

                return $existing->load('items');
            }
            $cart = $user->cartItems()->orderBy('product_id')->get();
            if ($cart->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }
            $coupon = null;
            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::where('code', $data['coupon_code'])->lockForUpdate()->first();
                if (! $coupon) {
                    throw ValidationException::withMessages(['coupon_code' => 'Coupon not found.']);
                }
            }
            $products = Product::with('vendor')->whereIn('id', $cart->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0;
            $variants = ProductVariant::whereIn('id', $cart->pluck('variant_id')->filter())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $reserved = [];
            foreach ($cart as $item) {
                $product = $products->get($item->product_id);
                $variant = $item->variant_id ? $variants->get($item->variant_id) : null;
                if (($item->variant_id && (! $variant || ! $variant->active || $variant->product_id !== $item->product_id || $variant->stock < $item->quantity)) || (! $item->variant_id && $product?->variants()->exists())) {
                    throw ValidationException::withMessages(['cart' => 'A selected size/color is unavailable. Update your cart.']);
                }
                $reserved[$item->product_id] = ($reserved[$item->product_id] ?? 0) + $item->quantity;
                if (! $product || $product->status !== 'active' || ! $product->vendor?->active || $product->vendor?->role !== 'vendor' || $product->stock < $reserved[$item->product_id]) {
                    throw ValidationException::withMessages(['cart' => 'One or more products are unavailable. Update your cart.']);
                }
                $subtotal += Money::cents($product->price) * $item->quantity;
            }
            $totals = $this->pricing->totals($subtotal, $coupon);
            $order = Order::create($totals + ['user_id' => $user->id, 'buyer_name' => $user->name, 'buyer_email' => $user->email, 'shipping_address' => $data['shipping_address'], 'status' => 'pending', 'coupon_id' => $coupon?->id, 'idempotency_key' => $data['idempotency_key'], 'request_hash' => $hash]);
            $threshold = (int) Setting::read('low_stock_threshold', '5');
            $order->forceFill(['payment_method' => $paymentMethod])->save();
            $order->forceFill(['shipping_cents' => $totals['shipping_cents']])->save();
            foreach ($cart as $item) {
                $product = $products[$item->product_id];
                $previousStock = $product->stock;
                $product->decrement('stock', $item->quantity);
                $variant = $item->variant_id ? $variants[$item->variant_id] : null;
                if ($variant) {
                    $variant->decrement('stock', $item->quantity);
                }
                $order->items()->create(['product_id' => $product->id, 'vendor_id' => $product->vendor_id, 'product_name' => $product->name, 'quantity' => $item->quantity, 'unit_price_cents' => Money::cents($product->price), 'status' => 'pending', 'inventory_reserved' => true]);
                $order->items()->latest('id')->firstOrFail()->update(['variant_id' => $item->variant_id, 'variant_label' => $variant?->label()]);
                if ($previousStock > $threshold && $product->stock <= $threshold) {
                    foreach (User::where('role', 'admin')->where('active', true)->get() as $admin) {
                        Mail::to($admin->email)->queue((new LowStockAlert($product->name, $product->stock))->afterCommit());
                    }
                }
            }
            if ($coupon) {
                $coupon->increment('uses');
            }
            $user->cartItems()->delete();
            Mail::to($user->email)->queue((new OrderConfirmation($order))->afterCommit());
            app(ActivityService::class)->order($order->load('items'));

            return $order->load('items');
        }, 3);
    }
}
