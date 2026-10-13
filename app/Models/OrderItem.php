<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'product_id', 'vendor_id', 'product_name', 'quantity', 'unit_price_cents', 'status', 'inventory_reserved', 'variant_id', 'variant_label'])]
class OrderItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['inventory_reserved' => 'boolean', 'quantity' => 'integer', 'unit_price_cents' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id')->withTrashed();
    }
}
