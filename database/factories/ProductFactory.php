<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->words(3, true), 'category_id' => Category::factory(), 'vendor_id' => User::factory()->state(['role' => 'vendor']), 'price' => '100.00', 'stock' => 20, 'status' => 'active', 'description' => fake()->paragraph()];
    }
}
