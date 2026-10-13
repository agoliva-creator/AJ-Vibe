<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FashionCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendor = User::where('role', 'vendor')->where('active', true)->orderBy('id')->firstOrFail();
        $items = [
            ['Classic White Cotton Tee', 'Shirts', 'shirt', '349.00', 40],
            ['Relaxed Everyday T-Shirt', 'Shirts', 'shirt', '399.00', 32],
            ['Red Street Sneakers', 'Shoes', 'shoes', '1899.00', 18],
            ['Everyday Running Sneakers', 'Shoes', 'shoes', '2199.00', 15],
            ['Classic Black Biker Jacket', 'Jackets', 'jacket', '2499.00', 12],
            ['Streetwear Zip Jacket', 'Jackets', 'jacket', '1999.00', 16],
            ['Everyday Baseball Cap', 'Hats', 'hat', '299.00', 45],
            ['Casual Weekend Cap', 'Hats', 'hat', '349.00', 30],
            ['Classic Denim Jeans', 'Pants & Jeans', 'jeans', '1199.00', 24],
            ['Weekend Denim Pants', 'Pants & Jeans', 'jeans', '999.00', 28],
            ['Cozy Knit Sweater', 'Sweaters', 'sweater', '899.00', 22],
            ['Everyday Knit Pullover', 'Sweaters', 'sweater', '1099.00', 20],
        ];
        foreach ($items as $item) {
            if (! Storage::disk(config('commerce.image_disk'))->exists('products/fashion/'.$item[2].'.jpg')) {
                throw new \RuntimeException('Missing fashion sample image: '.$item[2]);
            }
        }
        DB::transaction(function () use ($vendor, $items): void {
            foreach ($items as [$name, $categoryName, $image, $price, $stock]) {
                $category = Category::firstOrCreate(['slug' => Str::slug($categoryName)], ['name' => $categoryName, 'description' => 'Everyday fashion '.$categoryName.'.']);
                $product = Product::where('vendor_id', $vendor->id)->whereIn('name', [$name, "Men's ".$name])->where('image_path', 'products/fashion/'.$image.'.jpg')->first();
                if ($product) {
                    $product->update(['name' => "Men's ".$name, 'category_id' => $category->id, 'description' => "An everyday men's fashion essential for casual outfits and streetwear. Sample catalog item; photo is illustrative."]);

                    continue;
                }
                Product::firstOrCreate(['name' => "Men's ".$name, 'vendor_id' => $vendor->id], [
                    'category_id' => $category->id,
                    'description' => "An everyday men's fashion essential for casual outfits and streetwear. Sample catalog item; photo is illustrative.",
                    'price' => $price,
                    'stock' => $stock,
                    'status' => 'active',
                    'image_path' => 'products/fashion/'.$image.'.jpg',
                ]);
            }
        });
    }
}
