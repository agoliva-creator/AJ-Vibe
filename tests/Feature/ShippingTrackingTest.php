<?php

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);
test('shipping is included in checkout and snapshotted when settings change', function () {
    Mail::fake();
    Setting::create(['key' => 'shipping_fee_cents', 'value' => '10000']);
    $buyer = User::factory()->create();
    $product = Product::factory()->create(['price' => '200.00']);
    $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
    $this->actingAs($buyer)->get('/cart')->assertOk()->assertSee('Shipping');
    $order = app(CheckoutService::class)->place($buyer, ['shipping_address' => '123 Test Street', 'idempotency_key' => (string) Str::uuid()]);
    expect($order->shipping_cents)->toBe(10000);
    expect($order->total_cents)->toBe(30000);
    Setting::where('key', 'shipping_fee_cents')->update(['value' => '20000']);
    expect($order->fresh()->shipping_cents)->toBe(10000);
});
test('seller may update only their shipment and customer can see tracking', function () {
    $item = OrderItem::factory()->create();
    $otherItem = OrderItem::factory()->create(['order_id' => $item->order_id]);
    $seller = User::findOrFail($item->vendor_id);
    $this->actingAs($seller)->patch('/orders/'.$item->order_id.'/tracking', ['item_id' => $otherItem->id, 'courier' => 'J&T', 'tracking_number' => 'TEST123'])->assertNotFound();
    $this->patch('/orders/'.$item->order_id.'/tracking', ['item_id' => $item->id, 'courier' => 'J&T', 'tracking_number' => 'TEST123'])->assertSessionHasNoErrors();
    expect($item->fresh()->tracking_number)->toBe('TEST123');
    $buyer = $item->order->user;
    $this->actingAs($buyer)->get('/orders/'.$item->order_id)->assertOk()->assertSee('TEST123');
    $this->patch('/orders/'.$item->order_id.'/tracking', ['item_id' => $item->id, 'courier' => 'J&T', 'tracking_number' => 'TEST999'])->assertForbidden();
});
