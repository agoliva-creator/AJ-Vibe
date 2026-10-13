<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $vendor = $request->user()->isVendor();
        $data = ['id' => $this->id, 'status' => $this->status, 'created_at' => $this->created_at->toIso8601String(), 'currency' => config('commerce.currency'), 'shipping_address' => $this->shipping_address, 'buyer_name' => $this->buyer_name, 'items' => $this->items->map(fn ($item) => ['id' => $item->id, 'product_id' => $item->product_id, 'product_name' => $item->product_name, 'variant_id' => $item->variant_id, 'variant_label' => $item->variant_label, 'courier' => $item->courier, 'tracking_number' => $item->tracking_number, 'quantity' => $item->quantity, 'unit_price_cents' => $item->unit_price_cents, 'status' => $item->status])->all()];
        if (! $vendor) {
            $data['payment_method'] = $this->payment_method;
            $data += ['subtotal_cents' => $this->subtotal_cents, 'discount_cents' => $this->discount_cents, 'tax_cents' => $this->tax_cents, 'shipping_cents' => $this->shipping_cents, 'total_cents' => $this->total_cents];
        } else {
            $data['seller_subtotal_cents'] = $this->items->sum(fn ($item) => $item->quantity * $item->unit_price_cents);
        }

        return $data;
    }
}
