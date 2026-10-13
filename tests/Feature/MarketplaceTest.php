<?php

use App\Mail\LowStockAlert;
use App\Mail\OrderConfirmation;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
test('vendors cannot read update delete or impersonate another inventory owner', function () {
    $mine = User::factory()->create(['role' => 'vendor']);
    $other = Product::factory()->create();
    $data = ['name' => 'Stolen', 'description' => '', 'price' => '1.00', 'stock' => 1, 'category_id' => $other->category_id, 'status' => 'active'];
    $this->actingAs($mine)->get('/manage/products/'.$other->id)->assertForbidden();
    $this->put('/manage/products/'.$other->id, $data)->assertForbidden();
    $this->delete('/manage/products/'.$other->id)->assertForbidden();
    $this->post('/manage/products', $data + ['vendor_id' => $other->vendor_id])->assertSessionHasErrors('vendor_id');
    $this->post('/manage/products', $data)->assertSessionHasNoErrors()->assertRedirect();
    expect(Product::latest('id')->first()->vendor_id)->toBe($mine->id);
    $this->get('/manage/products')->assertDontSee($other->name);
});
test('guest carts merge into persistent carts at login', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create(['password' => 'Password12345']);
    CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);
    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 3])->assertRedirect('/cart');
    $this->get('/cart')->assertOk()->assertSee($product->name);
    $this->post('/login', ['email' => $user->email, 'password' => 'Password12345'])->assertRedirect('/dashboard')->assertSessionMissing('cart');
    $this->assertDatabaseHas('cart_items', ['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 5]);
});
test('cart synchronization is atomic and isolates customers', function () {
    $product = Product::factory()->create(['stock' => 3]);
    $customer = User::factory()->create();
    $other = User::factory()->create();
    CartItem::factory()->create(['user_id' => $other->id, 'product_id' => $product->id, 'quantity' => 1]);
    Sanctum::actingAs($customer, ['commerce']);
    $this->putJson('/api/v1/cart', ['items' => [['product_id' => $product->id, 'quantity' => 2]]])->assertOk()->assertJsonPath('data.subtotal_cents', 20000);
    $this->putJson('/api/v1/cart', ['items' => [['product_id' => $product->id, 'quantity' => 4]]])->assertUnprocessable();
    $this->assertDatabaseHas('cart_items', ['user_id' => $customer->id, 'quantity' => 2]);
    $this->putJson('/api/v1/cart', ['items' => []])->assertOk()->assertJsonCount(0, 'data.items');
    $this->assertDatabaseHas('cart_items', ['user_id' => $other->id, 'quantity' => 1]);
});
test('checkout calculates exact discounts taxes snapshots stock and queues mail', function () {
    Mail::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $buyer = User::factory()->create();
    $product = Product::factory()->create(['price' => '19.99', 'stock' => 6]);
    Coupon::factory()->create(['code' => 'SAVE10', 'value' => 10]);
    Setting::create(['key' => 'tax_rate_bps', 'value' => '1200']);
    CartItem::factory()->create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 2]);
    Sanctum::actingAs($buyer, ['commerce']);
    $response = $this->postJson('/api/v1/orders', ['shipping_address' => '123 Main Street, Manila', 'coupon_code' => 'save10', 'idempotency_key' => (string) Str::uuid(), 'total_cents' => 1])->assertCreated();
    $response->assertJsonPath('data.subtotal_cents', 3998)->assertJsonPath('data.discount_cents', 399)->assertJsonPath('data.tax_cents', 432)->assertJsonPath('data.total_cents', 4031);
    expect($product->fresh()->stock)->toBe(4);
    expect($buyer->cartItems()->count())->toBe(0);
    expect(Coupon::first()->uses)->toBe(1);
    Mail::assertQueued(OrderConfirmation::class);
    Mail::assertQueued(LowStockAlert::class, fn ($mail) => $mail->hasTo($admin->email));
    $product->update(['price' => '99.00', 'name' => 'Renamed']);
    $order = Order::first();
    expect($order->items->first()->unit_price_cents)->toBe(1999);
    expect($order->items->first()->product_name)->not->toBe('Renamed');
});
test('checkout retries do not double reserve stock or consume coupons', function () {
    Mail::fake();
    $buyer = User::factory()->create();
    $product = Product::factory()->create(['stock' => 5]);
    CartItem::factory()->create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 2]);
    Sanctum::actingAs($buyer, ['commerce']);
    $data = ['shipping_address' => '123 Main Street, Manila', 'idempotency_key' => (string) Str::uuid()];
    $first = $this->postJson('/api/v1/orders', $data)->assertCreated()->json('data.id');
    $this->postJson('/api/v1/orders', $data)->assertCreated()->assertJsonPath('data.id', $first);
    $this->postJson('/api/v1/orders', ['shipping_address' => 'Different address, Manila', 'idempotency_key' => $data['idempotency_key']])->assertConflict();
    expect(Order::count())->toBe(1);
    expect($product->fresh()->stock)->toBe(3);
    Mail::assertQueued(OrderConfirmation::class, 1);
});
test('checkout failure rolls back inventory orders and retains cart', function () {
    Mail::fake();
    $buyer = User::factory()->create();
    $first = Product::factory()->create(['stock' => 5]);
    $second = Product::factory()->create(['stock' => 1]);
    CartItem::factory()->create(['user_id' => $buyer->id, 'product_id' => $first->id, 'quantity' => 2]);
    CartItem::factory()->create(['user_id' => $buyer->id, 'product_id' => $second->id, 'quantity' => 2]);
    Sanctum::actingAs($buyer, ['commerce']);
    $this->postJson('/api/v1/orders', ['shipping_address' => '123 Main Street, Manila', 'idempotency_key' => (string) Str::uuid()])->assertUnprocessable();
    expect(Order::count())->toBe(0);
    expect($first->fresh()->stock)->toBe(5);
    expect($buyer->cartItems()->count())->toBe(2);
    Mail::assertNothingQueued();
});
test('expired exhausted and minimum purchase coupons are rejected', function () {
    Mail::fake();
    $buyer = User::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->create(['user_id' => $buyer->id, 'product_id' => $product->id]);
    Sanctum::actingAs($buyer, ['commerce']);
    foreach ([['expires_at' => now()->subDay()], ['usage_limit' => 1, 'uses' => 1], ['minimum_cents' => 10001]] as $attributes) {
        $coupon = Coupon::factory()->create($attributes);
        $this->postJson('/api/v1/orders', ['shipping_address' => '123 Main Street, Manila', 'coupon_code' => $coupon->code, 'idempotency_key' => (string) Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('coupon_code');
    }
    expect($product->fresh()->stock)->toBe(20);
});
test('customers cannot access another customer orders invoices or cancellation', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);
    $other = User::factory()->create();
    $this->actingAs($other);
    foreach (['', '/invoice', '/pdf'] as $suffix) {
        $this->get('/orders/'.$order->id.$suffix)->assertForbidden();
    }
    $this->post('/orders/'.$order->id.'/cancel')->assertForbidden();
    $this->get('/orders')->assertDontSee('#'.$order->id);
});
test('vendor fulfillment only touches owned lines and cannot skip states', function () {
    $order = Order::factory()->create();
    $mine = OrderItem::factory()->create(['order_id' => $order->id]);
    $other = OrderItem::factory()->create(['order_id' => $order->id]);
    Sanctum::actingAs($mine->vendor, ['commerce']);
    $this->getJson('/api/v1/orders/'.$order->id)->assertOk()->assertJsonCount(1, 'data.items')->assertJsonMissingPath('data.total_cents');
    $this->patchJson('/api/v1/orders/'.$order->id, ['item_id' => $other->id, 'status' => 'processing'])->assertNotFound();
    $this->patchJson('/api/v1/orders/'.$order->id, ['item_id' => $mine->id, 'status' => 'shipped'])->assertUnprocessable();
    $this->patchJson('/api/v1/orders/'.$order->id, ['item_id' => $mine->id, 'status' => 'processing'])->assertOk();
    expect($other->fresh()->status)->toBe('pending');
    expect($mine->fresh()->status)->toBe('processing');
    $this->postJson('/api/v1/orders/'.$order->id.'/cancel')->assertForbidden();
});
test('cancellation restores inventory once and releases coupon usage', function () {
    Mail::fake();
    $buyer = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);
    $coupon = Coupon::factory()->create();
    CartItem::factory()->create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 3]);
    Sanctum::actingAs($buyer, ['commerce']);
    $id = $this->postJson('/api/v1/orders', ['shipping_address' => '123 Main Street, Manila', 'coupon_code' => $coupon->code, 'idempotency_key' => (string) Str::uuid()])->assertCreated()->json('data.id');
    $this->postJson('/api/v1/orders/'.$id.'/cancel')->assertOk();
    $this->postJson('/api/v1/orders/'.$id.'/cancel')->assertOk();
    expect($product->fresh()->stock)->toBe(10);
    expect($coupon->fresh()->uses)->toBe(0);
    expect(Order::find($id)->status)->toBe('cancelled');
});
test('orders with a shipped line cannot be cancelled', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'status' => 'shipped']);
    $this->actingAs($order->user)->post('/orders/'.$order->id.'/cancel')->assertSessionHasErrors('order');
    expect($item->fresh()->status)->toBe('shipped');
});
test('reviews require delivered purchases and only authors can change them', function () {
    $buyer = User::factory()->create();
    $product = Product::factory()->create();
    $this->actingAs($buyer)->post('/products/'.$product->id.'/reviews', ['rating' => 5, 'body' => 'Excellent quality'])->assertForbidden();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'status' => 'delivered']);
    $this->post('/products/'.$product->id.'/reviews', ['rating' => 5, 'body' => 'Excellent quality'])->assertRedirect();
    $this->post('/products/'.$product->id.'/reviews', ['rating' => 4, 'body' => 'Updated experience'])->assertRedirect();
    expect(Review::count())->toBe(1);
    expect(Review::first()->rating)->toBe(4);
    $this->actingAs(User::factory()->create())->delete('/reviews/'.Review::first()->id)->assertForbidden();
});
test('admin cannot disable or demote the last active administrator', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->put('/manage/users/'.$admin->id, ['name' => $admin->name, 'email' => $admin->email, 'role' => 'customer', 'active' => 1])->assertSessionHasErrors('role');
    expect($admin->fresh()->role)->toBe('admin');
    $this->delete('/manage/users/'.$admin->id)->assertForbidden();
});
test('avatars are validated and replaced safely', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->put('/account', ['name' => 'New Name', 'avatar' => UploadedFile::fake()->image('avatar.png')])->assertSessionHasNoErrors()->assertRedirect();
    $path = $user->fresh()->avatar_path;
    Storage::disk('public')->assertExists($path);
    $this->put('/account', ['name' => 'New Name', 'avatar' => UploadedFile::fake()->create('avatar.svg')])->assertSessionHasErrors('avatar');
    expect($user->fresh()->avatar_path)->toBe($path);
});
test('sanctum tokens authenticate expire and can be revoked', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create(['password' => 'Password12345']);
    $response = $this->postJson('/api/v1/tokens', ['email' => $user->email, 'password' => 'Password12345'])->assertCreated();
    $token = $response->json('token');
    $this->withToken($token)->getJson('/api/v1/products/'.$product->id.'/inventory')->assertOk();
    expect($user->tokens()->first()->expires_at->isFuture())->toBeTrue();
    $this->withToken($token)->deleteJson('/api/v1/tokens/current')->assertNoContent();
    expect($user->tokens()->count())->toBe(0);
});
test('disabled accounts cannot log in or use existing tokens', function () {
    $user = User::factory()->create(['active' => false, 'password' => 'Password12345']);
    $this->post('/login', ['email' => $user->email, 'password' => 'Password12345'])->assertSessionHasErrors('email');
    $token = $user->createToken('old', ['commerce'])->plainTextToken;
    $this->withToken($token)->getJson('/api/v1/orders')->assertForbidden();
});
test('deleted products remain removable from carts and historical invoices survive', function () {
    $product = Product::factory()->create();
    $buyer = User::factory()->create();
    CartItem::factory()->create(['user_id' => $buyer->id, 'product_id' => $product->id]);
    $product->delete();
    $this->actingAs($buyer)->get('/cart')->assertOk()->assertSee('no longer available');
    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 0])->assertRedirect();
    expect($buyer->cartItems()->count())->toBe(0);
});
test('public catalog hides drafts disabled vendors and deleted products', function () {
    $visible = Product::factory()->create();
    $draft = Product::factory()->create(['status' => 'draft']);
    $disabled = Product::factory()->create();
    $disabled->vendor->forceFill(['active' => false])->save();
    $deleted = Product::factory()->create();
    $deleted->delete();
    $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $visible->id);
    $this->get('/products/'.$draft->id)->assertNotFound();
    $this->get('/products/'.$visible->id)->assertOk();
});
test('settings require admin and calculate fractional amounts with integer rounding', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->put('/settings', ['store_name' => 'New store', 'tax_rate_bps' => 1200, 'low_stock_threshold' => 3])->assertRedirect();
    $totals = app(PricingService::class)->totals(1999);
    expect($totals['tax_cents'])->toBe(240);
    $this->actingAs(User::factory()->create())->put('/settings', ['store_name' => 'Hacked', 'tax_rate_bps' => 0, 'low_stock_threshold' => 3])->assertForbidden();
});
test('archived order checkout keys cannot be reused', function () {
    Mail::fake();
    $buyer = User::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->create(['user_id' => $buyer->id, 'product_id' => $product->id]);
    Sanctum::actingAs($buyer, ['commerce']);
    $data = ['shipping_address' => '123 Main Street, Manila', 'idempotency_key' => (string) Str::uuid()];
    $id = $this->postJson('/api/v1/orders',$data)->assertCreated()->json('data.id');
    $this->postJson('/api/v1/orders/'.$id.'/cancel')->assertOk();
    $order = Order::find($id);
    $order->delete();
    $this->postJson('/api/v1/orders',$data)->assertConflict();
});
