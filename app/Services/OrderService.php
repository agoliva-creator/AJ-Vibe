<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function fulfill(User $user, Order $order, array $data): Order
    {
        Gate::forUser($user)->authorize('update', $order);

        return DB::transaction(function () use ($user, $order, $data): Order {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'cancelled') {
                throw ValidationException::withMessages(['status' => 'Cancelled orders cannot be fulfilled.']);
            }
            $query = $locked->items();
            if ($user->isVendor()) {
                $query->where('vendor_id', $user->id)->whereKey($data['item_id']);
            } elseif (! empty($data['item_id'])) {
                $query->whereKey($data['item_id']);
            }
            $items = $query->get();
            abort_if($items->isEmpty(), 404);
            $next = ['pending' => 'processing', 'processing' => 'shipped', 'shipped' => 'delivered'];
            foreach ($items as $item) {
                if ($item->status === $data['status']) {
                    continue;
                }
                if (($next[$item->status] ?? null) !== $data['status']) {
                    throw ValidationException::withMessages(['status' => 'Use pending → processing → shipped → delivered, one step at a time.']);
                }
                $item->update(['status' => $data['status']]);
            }
            $statuses = $locked->items()->pluck('status');
            $status = $statuses->every(fn ($status) => $status === 'delivered') ? 'delivered' : ($statuses->every(fn ($status) => in_array($status, ['shipped', 'delivered'])) ? 'shipped' : ($statuses->contains(fn ($status) => $status !== 'pending') ? 'processing' : 'pending'));
            $locked->update(['status' => $status]);

            return $locked->load('items');
        }, 3);
    }

    public function cancel(User $user, Order $order): Order
    {
        Gate::forUser($user)->authorize('cancel', $order);

        return DB::transaction(function () use ($order): Order {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'cancelled') {
                return $locked;
            }
            $items = $locked->items()->orderBy('product_id')->get();
            if ($items->contains(fn ($item) => in_array($item->status, ['shipped', 'delivered']))) {
                throw ValidationException::withMessages(['order' => 'Orders with shipped items cannot be cancelled.']);
            }
            foreach ($items as $item) {
                if ($item->inventory_reserved) {
                    Product::withTrashed()->whereKey($item->product_id)->lockForUpdate()->firstOrFail()->increment('stock', $item->quantity);
                    if ($item->variant_id) {
                        ProductVariant::whereKey($item->variant_id)->lockForUpdate()->firstOrFail()->increment('stock', $item->quantity);
                    }
                }
                $item->update(['status' => 'cancelled', 'inventory_reserved' => false]);
            }
            if ($locked->coupon_id) {
                $coupon = Coupon::withTrashed()->whereKey($locked->coupon_id)->lockForUpdate()->firstOrFail();
                if ($coupon->uses > 0) {
                    $coupon->decrement('uses');
                }
            }
            $locked->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return $locked->load('items');
        }, 3);
    }

    public function delete(User $user, Order $order): void
    {
        DB::transaction(function () use ($user, $order): void {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('delete', $locked);
            $locked->delete();
        }, 3);
    }
}
