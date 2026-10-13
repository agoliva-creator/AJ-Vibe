<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description', 'price', 'stock', 'category_id', 'vendor_id', 'status', 'image_path'])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'stock' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'active')->whereHas('vendor', fn (Builder $vendor) => $vendor->where('active', true)->where('role', 'vendor'));
    }

    public function scopeFashionOrder(Builder $query): Builder
    {
        return $query->orderByRaw('CASE WHEN LOWER(products.name) LIKE ? OR LOWER(products.name) LIKE ? THEN 0 WHEN LOWER(products.name) LIKE ? OR LOWER(products.name) LIKE ? OR products.category_id IN (SELECT id FROM categories WHERE LOWER(name) LIKE ?) THEN 1 ELSE 2 END', ['%hoodie%', '%hooded%', '%shirt%', '%tee%', '%shirt%'])->latest()->orderByDesc('id');
    }
}
