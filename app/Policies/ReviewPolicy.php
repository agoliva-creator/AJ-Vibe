<?php

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function create(User $user, int $productId): bool
    {
        return $user->role === 'customer' && OrderItem::where('product_id', $productId)->where('status', 'delivered')->whereHas('order', fn ($query) => $query->where('user_id', $user->id))->exists();
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id && $this->create($user, $review->product_id);
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin() || $review->user_id === $user->id;
    }
}
