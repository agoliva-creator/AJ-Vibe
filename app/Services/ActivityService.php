<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\CommerceActivity;

class ActivityService
{
    public function cart(Product $product): void
    {
        $recipients = User::where('active', true)->where(function ($query) use ($product): void {
            $query->where('role', 'admin')->orWhere(function ($query) use ($product): void {
                $query->where('role', 'vendor')->whereKey($product->vendor_id);
            });
        })->get();
        foreach ($recipients as $recipient) {
            $recipient->notify(new CommerceActivity('An item was added to a cart: '.$product->name, route('records.show', ['products', $product->id], false)));
        }
    }

    public function order(Order $order): void
    {
        $vendorIds = $order->items->pluck('vendor_id')->unique();
        $recipients = User::where('active', true)->where(function ($query) use ($order, $vendorIds): void {
            $query->where('role', 'admin')->orWhereKey($order->user_id)->orWhere(function ($query) use ($vendorIds): void {
                $query->where('role', 'vendor')->whereIn('id', $vendorIds);
            });
        })->get();
        foreach ($recipients as $recipient) {
            $recipient->notify(new CommerceActivity('New order #'.$order->id.' has been placed.', route('orders.show', $order, false)));
        }
    }
}
