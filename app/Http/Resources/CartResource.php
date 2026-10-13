<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        foreach ($this->resource['items'] as $item) {
            $item['product']->loadMissing('variants');
        }

        return ['items' => array_map(fn ($item) => ['product' => new ProductResource($item['product']), 'variant_id' => $item['variant_id'], 'variant_label' => $item['variant']?->label(), 'quantity' => $item['quantity'], 'line_total_cents' => $item['line_total_cents'], 'available' => (bool) $item['available']], $this->resource['items']), 'subtotal_cents' => $this->resource['subtotal_cents'], 'discount_cents' => $this->resource['discount_cents'], 'tax_cents' => $this->resource['tax_cents'], 'shipping_cents' => $this->resource['shipping_cents'], 'total_cents' => $this->resource['total_cents'], 'currency' => config('commerce.currency')];
    }
}
