<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVendor();
    }

    public function view(User $user, Category $record): bool
    {
        return $user->isVendor();
    }

    public function create(User $user): bool
    {
        return $user->isVendor();
    }

    public function update(User $user, Category $record): bool
    {
        return $user->isVendor();
    }

    public function delete(User $user, Category $record): bool
    {
        return $user->isVendor();
    }
}
