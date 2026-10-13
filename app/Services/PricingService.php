<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Setting;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function totals(int $subtotal, ?Coupon $coupon = null): array
    {
        $discount = 0;
        if ($coupon) {
            if (! $coupon->active || ($coupon->starts_at && $coupon->starts_at->isFuture()) || ($coupon->expires_at && $coupon->expires_at->isPast()) || ($coupon->usage_limit !== null && $coupon->uses >= $coupon->usage_limit) || $subtotal < $coupon->minimum_cents) {
                throw ValidationException::withMessages(['coupon_code' => 'This coupon is not available for this order.']);
            }
            $discount = min($subtotal, $coupon->type === 'percent' ? intdiv($subtotal * $coupon->value, 100) : $coupon->value);
        }
        $tax = intdiv(($subtotal - $discount) * (int) Setting::read('tax_rate_bps', '0') + 5000, 10000);

        $shipping = $subtotal > 0 ? (int) Setting::read('shipping_fee_cents', '0') : 0;

        return ['subtotal_cents' => $subtotal, 'discount_cents' => $discount, 'tax_cents' => $tax, 'shipping_cents' => $shipping, 'total_cents' => $subtotal - $discount + $tax + $shipping];
    }
}
