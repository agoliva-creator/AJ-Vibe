<?php

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'description' => $this->description, 'price' => $this->price, 'currency' => config('commerce.currency'), 'variants' => $this->whenLoaded('variants', fn () => $this->variants->where('active', true)->map(fn ($variant) => ['id' => $variant->id, 'size' => $variant->size, 'color' => $variant->color, 'stock' => $variant->stock])->values()), 'stock' => $this->stock, 'image_url' => app(ImageService::class)->url($this->image_path), 'category' => ['id' => $this->category->id, 'name' => $this->category->name, 'slug' => $this->category->slug], 'seller' => $this->vendor ? ['id' => $this->vendor->id, 'name' => $this->vendor->name] : null, 'rating' => $this->reviews_avg_rating];
    }
}
