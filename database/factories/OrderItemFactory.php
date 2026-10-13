<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return ['order_id' => Order::factory(), 'product_id' => Product::factory(), 'vendor_id' => fn (array $attributes) => Product::findOrFail($attributes['product_id'])->vendor_id, 'product_name' => 'Test product', 'quantity' => 1, 'unit_price_cents' => 10000, 'status' => 'pending', 'inventory_reserved' => true];
    }
}
