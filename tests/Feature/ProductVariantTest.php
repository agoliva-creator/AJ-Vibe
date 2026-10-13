<?php

use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);
test('different variants remain separate and checkout cancellation restores their stock exactly once', function () {
    Mail::fake();
    $product = Product::factory()->create(['stock' => 7]);
    $small = $product->variants()->create(['size' => 'S', 'color' => 'Black', 'stock' => 3]);
    $large = $product->variants()->create(['size' => 'L', 'color' => 'Black', 'stock' => 4]);
    $buyer = User::factory()->create();
    $this->actingAs($buyer);
    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 1])->assertSessionHasErrors('quantity');
    foreach ([$small, $large] as $variant) {
        $this->post('/cart', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2])->assertSessionHasNoErrors();
    }
    expect($buyer->cartItems()->count())->toBe(2);
    $this->get('/cart')->assertOk()->assertSee('S / Black')->assertSee('L / Black');
    $order = app(CheckoutService::class)->place($buyer, ['shipping_address' => '123 Test Street', 'idempotency_key' => (string) Str::uuid()]);
    expect($small->fresh()->stock)->toBe(1);
    expect($large->fresh()->stock)->toBe(2);
    expect($product->fresh()->stock)->toBe(3);
    expect($order->items->pluck('variant_label')->all())->toContain('S / Black', 'L / Black');
    app(OrderService::class)->cancel($buyer, $order);
    app(OrderService::class)->cancel($buyer, $order);
    expect($small->fresh()->stock)->toBe(3);
    expect($large->fresh()->stock)->toBe(4);
    expect($product->fresh()->stock)->toBe(7);
});
test('cart rejects mismatched variants and stock reduced before checkout', function () {
    Mail::fake();
    $product = Product::factory()->create(['stock' => 3]);
    $variant = $product->variants()->create(['size' => 'M', 'color' => 'Blue', 'stock' => 3]);
    $other = Product::factory()->create();
    $buyer = User::factory()->create();
    $this->actingAs($buyer)->post('/cart', ['product_id' => $other->id, 'variant_id' => $variant->id, 'quantity' => 1])->assertSessionHasErrors('quantity');
    $this->post('/cart', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 4])->assertSessionHasErrors('quantity');
    $this->post('/cart', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 3])->assertSessionHasNoErrors();
    $variant->update(['stock' => 1]);
    $this->post('/orders', ['shipping_address' => '123 Test Street', 'idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('cart');
    expect($buyer->orders()->count())->toBe(0);
    expect($product->fresh()->stock)->toBe(3);
});
test('vendor can save variant rows with automatic total stock', function () {
    $product = Product::factory()->create();
    $this->actingAs($product->vendor)->put('/manage/products/'.$product->id, ['name' => $product->name, 'category_id' => $product->category_id, 'price' => $product->price, 'stock' => 99, 'status' => 'active', 'variant_rows' => "M | Black | 3\nL | Black | 4"])->assertSessionHasNoErrors();
    expect($product->fresh()->stock)->toBe(7);
    expect($product->variants()->count())->toBe(2);
    $this->actingAs(User::factory()->create())->get('/products/'.$product->id)->assertOk()->assertSee('M / Black');
});

test('cart variant edits merge matching selections and reject insufficient stock without losing items', function () {
    $product = Product::factory()->create(['stock' => 8]);
    $small = $product->variants()->create(['size' => 'S', 'color' => 'Black', 'stock' => 4]);
    $large = $product->variants()->create(['size' => 'L', 'color' => 'Black', 'stock' => 4]);
    $buyer = User::factory()->create();
    $this->actingAs($buyer);
    foreach ([$small, $large] as $variant) {
        $this->post('/cart', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2])->assertSessionHasNoErrors();
    }
    $payload = ['product_id' => $product->id, 'original_variant_id' => $small->id, 'variant_id' => $large->id, 'quantity' => 3];
    $this->post('/cart', $payload)->assertSessionHasErrors('quantity');
    expect($buyer->cartItems()->count())->toBe(2);
    $payload['quantity'] = 2;
    $this->post('/cart', $payload)->assertSessionHasNoErrors();
    expect($buyer->cartItems()->count())->toBe(1);
    expect($buyer->cartItems()->first()->variant_id)->toBe($large->id);
    expect($buyer->cartItems()->first()->quantity)->toBe(4);
});
