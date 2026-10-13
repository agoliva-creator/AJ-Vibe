<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSizeStockSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $products = Product::with(['category', 'variants'])->orderBy('id')->lockForUpdate()->get();
            foreach ($products as $product) {
                $category = strtolower($product->category->name.' '.$product->name);
                $sizes = str_contains($category, 'shoe') || str_contains($category, 'sneaker') ? ['EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44'] : (str_contains($category, 'hat') || str_contains($category, 'cap') || str_contains($category, 'tote') || str_contains($category, 'organizer') || str_contains($category, 'notebook') ? ['One Size'] : ['S', 'M', 'L', 'XL']);
                $colors = $product->variants->where('active', true)->pluck('color')->unique()->values();
                if ($colors->isEmpty()) {
                    $color = 'Original';
                    foreach (['black' => 'Black', 'white' => 'White', 'red' => 'Red', 'blue' => 'Blue', 'gray' => 'Gray', 'grey' => 'Gray', 'brown' => 'Brown'] as $word => $label) {
                        if (str_contains(strtolower($product->name), $word)) {
                            $color = $label;
                            break;
                        }
                    }
                    $colors = collect([$color]);
                }
                foreach ($product->variants->where('active', true) as $existing) {
                    $existing->update(['stock' => random_int(5, 20)]);
                }
                foreach ($colors as $color) {
                    foreach ($sizes as $size) {
                        $product->variants()->firstOrCreate(['size' => $size, 'color' => $color], ['stock' => random_int(5, 20), 'active' => true]);
                    }
                }
                $product->update(['stock' => $product->variants()->sum('stock')]);
            }
            $this->command?->info('Updated sizes and stock for '.$products->count().' products. Existing variants and order history retained.');
        });
    }
}
