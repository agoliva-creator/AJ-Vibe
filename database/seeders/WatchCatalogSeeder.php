<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WatchCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $vendor = User::where('role', 'vendor')->where('active', true)->orderBy('id')->firstOrFail();
        $items = [['Classic Everyday Watch', 'classic', '1499.00', 18], ['Leather Strap Dress Watch', 'leather', '1899.00', 15], ['Minimal Silver Watch', 'steel', '1299.00', 22]];
        foreach ($items as $item) {
            if (! Storage::disk(config('commerce.image_disk'))->exists('products/watches/'.$item[1].'.jpg')) {
                throw new \RuntimeException('Missing watch product image.');
            }
        }
        DB::transaction(function () use ($vendor, $items): void {
            $category = Category::firstOrCreate(['slug' => 'watches'], ['name' => 'Watches', 'description' => 'Everyday timepieces and polished accessories.']);
            foreach ($items as [$name,$photo,$price,$stock]) {
                Product::firstOrCreate(['name' => $name, 'vendor_id' => $vendor->id], ['category_id' => $category->id, 'price' => $price, 'stock' => $stock, 'status' => 'active', 'image_path' => 'products/watches/'.$photo.'.jpg', 'description' => 'A versatile watch for everyday outfits. No size selection required. Sample catalog product; photo is illustrative.']);
            }
        });
    }
}
