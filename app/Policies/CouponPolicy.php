<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVendor();
    }

    public function view(User $user, Coupon $record): bool
    {
        return $user->isVendor();
    }

    public function create(User $user): bool
    {
        return $user->isVendor();
    }

    public function update(User $user, Coupon $record): bool
    {
        return $user->isVendor();
    }

    public function delete(User $user, Coupon $record): bool
    {
        return $user->isVendor();
    }
}
