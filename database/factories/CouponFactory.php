<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => strtoupper(fake()->unique()->bothify('SAVE####')), 'type' => 'percent', 'value' => 10, 'minimum_cents' => 0, 'active' => true];
    }
}
