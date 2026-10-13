<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isVendor();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isAdmin() || ($user->isVendor() && $product->vendor_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isVendor();
    }

    public function update(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }
}
