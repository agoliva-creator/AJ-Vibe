<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Coupon $record): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Coupon $record): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Coupon $record): bool
    {
        return $user->isAdmin();
    }
}
