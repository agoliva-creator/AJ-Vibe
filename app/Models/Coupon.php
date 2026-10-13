<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'type', 'value', 'minimum_cents', 'usage_limit', 'uses', 'active', 'starts_at', 'expires_at'])]
class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['active' => 'boolean', 'starts_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
