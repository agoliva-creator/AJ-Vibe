<?php

use App\Mail\WelcomeMail;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
test('catalog and welcome email escape user content and bind search input', function () {
    $dangerous = '<script>alert("xss")</script>';
    $product = Product::factory()->create(['name' => $dangerous, 'description' => $dangerous]);
    $this->get('/products/'.$product->id)->assertOk()->assertSee($dangerous)->assertDontSee($dangerous, false);
    $html = (new WelcomeMail($dangerous))->render();
    expect($html)->toContain('&lt;script&gt;')->not->toContain($dangerous);
    $this->getJson('/api/v1/products?search='.urlencode("' OR 1=1 --"))->assertOk()->assertJsonCount(0, 'data');
});
test('expired tokens return 401 and missing abilities return 403', function () {
    $this->freezeTime();
    $user = User::factory()->create();
    $token = $user->createToken('expired', ['commerce'], now()->subMinute())->plainTextToken;
    $this->withToken($token)->getJson('/api/v1/orders')->assertUnauthorized();
    Sanctum::actingAs($user, []);
    $this->getJson('/api/v1/orders')->assertForbidden();
});
test('the complete order fulfillment lifecycle aggregates and allows review', function () {
    Mail::fake();
    $product = Product::factory()->create();
    $buyer = User::factory()->create();
    CartItem::factory()->create(['user_id' => $buyer->id, 'product_id' => $product->id]);
    Sanctum::actingAs($buyer, ['commerce']);
    $id = $this->postJson('/api/v1/orders', ['shipping_address' => '123 Main Street, Manila', 'idempotency_key' => (string) Str::uuid()])->assertCreated()->json('data.id');
    $item = Order::find($id)->items->first();
    Sanctum::actingAs($product->vendor, ['commerce']);
    foreach (['processing', 'shipped', 'delivered'] as $status) {
        $this->patchJson('/api/v1/orders/'.$id, ['item_id' => $item->id, 'status' => $status])->assertOk()->assertJsonPath('data.status', $status);
    }
    $this->actingAs($buyer)->post('/products/'.$product->id.'/reviews', ['rating' => 5, 'body' => 'Delivered exactly as expected'])->assertRedirect();
    $this->assertDatabaseHas('reviews', ['user_id' => $buyer->id, 'product_id' => $product->id, 'rating' => 5]);
});
test('role and password changes revoke existing api tokens', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $customer->createToken('old', ['commerce']);
    $this->actingAs($admin)->put('/manage/users/'.$customer->id, ['name' => $customer->name, 'email' => $customer->email, 'role' => 'vendor', 'active' => 1, 'password' => 'ChangedPass12345'])->assertSessionHasNoErrors()->assertRedirect();
    expect($customer->tokens()->count())->toBe(0);
    expect($customer->fresh()->role)->toBe('vendor');
});
test('admin archives cancelled orders without destroying history', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::factory()->create(['status' => 'cancelled']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'status' => 'cancelled', 'inventory_reserved' => false]);
    $this->actingAs($admin)->delete('/orders/'.$order->id)->assertRedirect('/orders');
    $this->assertSoftDeleted($order);
    $this->assertModelExists($item);
    $this->get('/orders/'.$order->id)->assertNotFound();
});
test('fixed coupons cannot exceed the subtotal', function () {
    $coupon = Coupon::factory()->create(['type' => 'fixed', 'value' => 50000]);
    $totals = app(PricingService::class)->totals(10000, $coupon);
    expect($totals)->toBe(['subtotal_cents' => 10000, 'discount_cents' => 10000, 'tax_cents' => 0, 'shipping_cents' => 0, 'total_cents' => 0]);
});
test('production seeding creates a private admin and never demo identities', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['commerce.demo' => true, 'commerce.admin_email' => 'private-admin@example.com', 'commerce.admin_password' => 'PrivateAdmin12345']);
    $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
    $this->assertDatabaseHas('users', ['email' => 'private-admin@example.com', 'role' => 'admin']);
    $this->assertDatabaseMissing('users', ['email' => 'admin@commerce.test']);
    $admin = User::first();
    $hash = $admin->password;
    config(['commerce.admin_password' => 'AnotherSecret12345']);
    $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
    expect($admin->fresh()->password)->toBe($hash);
});
test('product policies grant ownership only to the vendor and control to admin', function () {
    $product = Product::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $other = User::factory()->create(['role' => 'vendor']);
    $customer = User::factory()->create();
    expect(Gate::forUser($product->vendor)->allows('update', $product))->toBeTrue();
    expect(Gate::forUser($admin)->allows('delete', $product))->toBeTrue();
    expect(Gate::forUser($other)->allows('update', $product))->toBeFalse();
    expect(Gate::forUser($customer)->allows('update', $product))->toBeFalse();
});
