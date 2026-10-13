<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'buyer_name' => fake()->name(), 'buyer_email' => fake()->safeEmail(), 'shipping_address' => fake()->address(), 'subtotal_cents' => 10000, 'discount_cents' => 0, 'tax_cents' => 0, 'total_cents' => 10000, 'status' => 'pending'];
    }
}
