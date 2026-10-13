<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || ($user->isVendor() ? $order->items()->where('vendor_id', $user->id)->exists() : $order->user_id === $user->id);
    }

    public function update(User $user, Order $order): bool
    {
        return ($user->isAdmin() || $user->isVendor()) && $this->view($user, $order);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->isAdmin() || ($user->role === 'customer' && $order->user_id === $user->id);
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin() && $order->status === 'cancelled';
    }
}
