<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'buyer_name', 'buyer_email', 'shipping_address', 'status', 'shipping_cents', 'subtotal_cents', 'discount_cents', 'tax_cents', 'total_cents', 'coupon_id', 'idempotency_key', 'request_hash', 'cancelled_at', 'customer_id', 'product_id', 'quantity', 'unit_price'])]
class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['cancelled_at' => 'datetime', 'subtotal_cents' => 'integer', 'discount_cents' => 'integer', 'tax_cents' => 'integer', 'total_cents' => 'integer'];
    }

    protected $attributes = ['shipping_cents' => 0];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query->whereRaw('1 = 0');
        }

        return $user->isVendor() ? $query->whereHas('items', fn (Builder $items) => $items->where('vendor_id', $user->id)) : $query->where('user_id', $user->id);
    }
}
