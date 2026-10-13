<?php

use App\Http\Controllers\ManagementController;
use App\Mail\OrderConfirmation;
use App\Mail\WelcomeMail;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);
test('registration always creates a customer and logs them in', function () {
    Mail::fake();
    $this->post('/register', ['name' => 'Buyer', 'email' => 'buyer@test.com', 'password' => 'Password12345', 'password_confirmation' => 'Password12345', 'role' => 'admin'])->assertRedirect('/dashboard');
    expect(User::first()->role)->toBe('customer');
    $this->assertAuthenticated();
    Mail::assertQueued(WelcomeMail::class);
});
test('login and logout use the session', function () {
    $user = User::factory()->create(['password' => 'Password12345']);
    $this->post('/login', ['email' => $user->email, 'password' => 'Password12345'])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
    $response = $this->get('/dashboard')->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $response->assertSee('data-authenticated="true"', false);
    $this->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
    foreach (['/dashboard', '/account', '/orders', '/notifications'] as $page) {
        $this->get($page)->assertRedirect('/login');
    }
});
test('role boundaries apply to pages and mutations', function () {
    $customer = User::factory()->create();
    $this->actingAs($customer)->get('/manage/products')->assertForbidden();
    $this->get('/manage/users')->assertForbidden();
    $vendor = User::factory()->create(['role' => 'vendor']);
    $this->actingAs($vendor)->get('/manage/products')->assertOk();
    $this->get('/manage/categories')->assertForbidden();
    $this->get('/settings')->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/manage/users')->assertOk();
});

test('profile email and password changes require current credentials and revoke api tokens', function () {
    $user = User::factory()->create(['password' => 'Password12345', 'email_verified_at' => now()]);
    $user->createToken('existing');
    $this->actingAs($user);
    $payload = ['name' => 'Updated profile', 'email' => 'updated@test.com', 'password' => 'NewPassword12345', 'password_confirmation' => 'NewPassword12345'];
    $this->put('/account', $payload)->assertSessionHasErrors('current_password');
    $this->put('/account', $payload + ['current_password' => 'WrongPassword123'])->assertSessionHasErrors('current_password');
    expect($user->fresh()->email)->not->toBe('updated@test.com');
    $this->put('/account', $payload + ['current_password' => 'Password12345'])->assertSessionHasNoErrors()->assertRedirect();
    $updated = $user->fresh();
    expect($updated->email)->toBe('updated@test.com');
    expect($updated->email_verified_at)->toBeNull();
    expect(Hash::check('NewPassword12345', $updated->password))->toBeTrue();
    expect($updated->tokens()->count())->toBe(0);
    $this->assertAuthenticatedAs($user);
    $this->post('/logout')->assertRedirect('/login');
    $this->post('/login', ['email' => 'updated@test.com', 'password' => 'NewPassword12345'])->assertSessionHasNoErrors()->assertRedirect();
});

test('profile settings reject duplicate emails and unconfirmed passwords', function () {
    $user = User::factory()->create(['password' => 'Password12345']);
    $other = User::factory()->create();
    $this->actingAs($user)->put('/account', ['name' => $user->name, 'email' => $other->email, 'current_password' => 'Password12345'])->assertSessionHasErrors('email');
    $this->put('/account', ['name' => $user->name, 'password' => 'NewPassword12345', 'password_confirmation' => 'DifferentPassword123', 'current_password' => 'Password12345'])->assertSessionHasErrors('password');
    expect(Hash::check('Password12345', $user->fresh()->password))->toBeTrue();
});
test('catalog management modules support complete crud', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $vendor = User::factory()->create(['role' => 'vendor']);
    $category = Category::factory()->create();
    $data = [
        'categories' => ['name' => 'New category'],
        'products' => ['name' => 'New product', 'category_id' => $category->id, 'vendor_id' => $vendor->id, 'price' => '250.00', 'stock' => 10, 'status' => 'active'],
        'users' => ['name' => 'New account', 'email' => 'newaccount@test.com', 'password' => 'Password12345', 'role' => 'customer', 'active' => 1],
        'coupons' => ['code' => 'TEST10', 'type' => 'percent', 'value' => 10, 'minimum_cents' => 0, 'active' => 1],
    ];
    foreach ($data as $entity => $payload) {
        $this->post('/manage/'.$entity, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $class = ManagementController::MODULES[$entity];
        $record = $class::latest('id')->first();
        $this->get('/manage/'.$entity)->assertOk();
        $this->get('/manage/'.$entity.'/'.$record->id)->assertOk();
        $this->put('/manage/'.$entity.'/'.$record->id, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->delete('/manage/'.$entity.'/'.$record->id)->assertRedirect();
    }
});
test('image uploads are stored replaced and validated', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['role' => 'vendor']));
    $category = Category::factory()->create();
    $data = ['name' => 'Photo product', 'category_id' => $category->id, 'price' => '25.00', 'stock' => 3, 'status' => 'active'];
    $this->post('/manage/products', $data + ['image' => UploadedFile::fake()->image('product.jpg')])->assertSessionHasNoErrors()->assertRedirect();
    $product = Product::first();
    $original = $product->image_path;
    Storage::disk('public')->assertExists($original);
    $this->put('/manage/products/'.$product->id, $data + ['image' => UploadedFile::fake()->image('replacement.png')])->assertSessionHasNoErrors()->assertRedirect();
    Storage::disk('public')->assertMissing($original);
    Storage::disk('public')->assertExists($product->fresh()->image_path);
    $this->post('/manage/products', $data + ['image' => UploadedFile::fake()->create('bad.txt')])->assertSessionHasErrors('image');
});
test('validation rejects invalid prices', function () {
    $this->actingAs(User::factory()->create(['role' => 'vendor']));
    $category = Category::factory()->create();
    $this->post('/manage/products', ['name' => 'Bad', 'category_id' => $category->id, 'price' => -1, 'stock' => 0, 'status' => 'active'])->assertSessionHasErrors('price');
});

test('profile uploads display on account and navigation using the public storage url', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user)->put('/account', ['name' => $user->name, 'avatar' => UploadedFile::fake()->image('profile.png')])
        ->assertSessionHasNoErrors()->assertRedirect();
    $path = $user->fresh()->avatar_path;
    Storage::disk('public')->assertExists($path);
    $this->get('/account')->assertOk()->assertSee('/storage/'.$path, false);
    $this->get('/dashboard')->assertOk()->assertSee('/storage/'.$path, false);
    $this->put('/account', ['name' => $user->name, 'avatar' => UploadedFile::fake()->image('new-profile.jpg')])->assertSessionHasNoErrors();
    Storage::disk('public')->assertMissing($path);
    Storage::disk('public')->assertExists($user->fresh()->avatar_path);
});
test('orders provide invoices pdfs and queued receipts', function () {
    Mail::fake();
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'total_cents' => 20000, 'subtotal_cents' => 20000]);
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 2, 'unit_price_cents' => 10000]);
    $this->actingAs($customer)->get('/orders/'.$order->id.'/invoice')->assertOk()->assertSee('200.00');
    $response = $this->get('/orders/'.$order->id.'/pdf')->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
    $this->post('/orders/'.$order->id.'/email')->assertRedirect();
    Mail::assertQueued(OrderConfirmation::class, fn ($mail) => $mail->hasTo($order->buyer_email));
});
test('referenced categories cannot be deleted', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $product = Product::factory()->create();
    $this->delete('/manage/categories/'.$product->category_id)->assertSessionHasErrors('record');
    $this->assertDatabaseHas('categories', ['id' => $product->category_id]);
});

test('admin permanently removes an unreferenced user and their profile file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/delete.jpg', 'sample');
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['avatar_path' => 'avatars/delete.jpg']);
    $user->createToken('old');
    $this->actingAs($admin)->delete('/manage/users/'.$user->id)->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    Storage::disk('public')->assertMissing('avatars/delete.jpg');
});

test('admin deletion removes a buyer while preserving their order history', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $this->actingAs($admin)->delete('/manage/users/'.$buyer->id)->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseMissing('users', ['id' => $buyer->id]);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => null]);
});

test('admin can delete a seller while retaining products and order items', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $product = Product::factory()->create();
    $sellerId = $product->vendor_id;
    $item = OrderItem::factory()->create(['product_id' => $product->id]);
    $this->actingAs($admin)->delete('/manage/users/'.$sellerId)->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('users', ['id' => $sellerId]);
    $this->assertDatabaseHas('products', ['id' => $product->id, 'vendor_id' => null, 'status' => 'draft']);
    $this->assertDatabaseHas('order_items', ['id' => $item->id, 'vendor_id' => null]);
    $this->get('/orders/'.$item->order_id)->assertOk();
});
test('workspace pages render for admin and vendor', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $product = Product::factory()->create();
    Coupon::factory()->create();
    $this->actingAs($admin);
    foreach (['/dashboard', '/account', '/api-guide', '/settings', '/orders'] as $url) {
        $this->get($url)->assertOk();
    }
    foreach (['users', 'products', 'categories', 'coupons'] as $entity) {
        $class = ManagementController::MODULES[$entity];
        $id = $class::first()->id;
        $this->get('/manage/'.$entity)->assertOk();
        $this->get('/manage/'.$entity.'/create')->assertOk();
        $this->get('/manage/'.$entity.'/'.$id)->assertOk();
        $this->get('/manage/'.$entity.'/'.$id.'/edit')->assertOk();
    }
    $this->actingAs($product->vendor)->get('/dashboard')->assertOk();
    $this->get('/manage/products/'.$product->id.'/edit')->assertOk();
});
test('private api requires authentication while catalog is public', function () {
    $this->getJson('/api/v1/orders')->assertUnauthorized();
    $this->getJson('/api/v1/products')->assertOk();
});

test('checkout saves selected payment preference and rejects unsupported methods', function () {
    Mail::fake();
    $buyer = User::factory()->create();
    $product = Product::factory()->create();
    $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
    $payload = ['shipping_address' => '123 Test Street City', 'idempotency_key' => (string) Str::uuid(), 'payment_method' => 'unsupported'];
    $this->actingAs($buyer)->post('/orders', $payload)->assertSessionHasErrors('payment_method');
    $payload['payment_method'] = 'gcash';
    $this->post('/orders', $payload)->assertSessionHasNoErrors()->assertRedirect();
    $order = $buyer->orders()->firstOrFail();
    expect($order->payment_method)->toBe('gcash');
    $this->get('/orders/'.$order->id)->assertOk()->assertSee('GCash')->assertSee('Awaiting payment');
    $payload['payment_method'] = 'credit_card';
    $this->post('/orders', $payload)->assertStatus(409);
});

test('cart activity alerts only admins and the owning seller and notifications remain private', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $product = Product::factory()->create();
    $otherSeller = User::factory()->create(['role' => 'vendor']);
    $customer = User::factory()->create();
    $this->actingAs($customer)->post('/cart', ['product_id' => $product->id, 'quantity' => 1])->assertRedirect();
    expect($admin->unreadNotifications()->count())->toBe(1);
    expect($product->vendor->unreadNotifications()->count())->toBe(1);
    expect($otherSeller->unreadNotifications()->count())->toBe(0);
    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 1])->assertRedirect();
    expect($admin->unreadNotifications()->count())->toBe(1);
    $this->get('/notifications/count')->assertOk()->assertJson(['count' => 0]);
    $this->get('/notifications')->assertOk()->assertDontSee('An item was added');
    $this->actingAs($admin)->get('/notifications')->assertOk()->assertSee($product->name);
    $this->post('/notifications/read')->assertRedirect();
    $this->get('/notifications/count')->assertJson(['count' => 0]);
    expect($product->vendor->unreadNotifications()->count())->toBe(1);
});

test('checkout notifies buyer admin and involved seller once for an idempotent order', function () {
    Mail::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $product = Product::factory()->create();
    $otherSeller = User::factory()->create(['role' => 'vendor']);
    $buyer = User::factory()->create();
    $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
    $checkout = app(CheckoutService::class);
    $data = ['shipping_address' => '123 Test Street', 'idempotency_key' => (string) Str::uuid()];
    $order = $checkout->place($buyer, $data);
    $checkout->place($buyer, $data);
    foreach ([$buyer, $admin, $product->vendor] as $recipient) {
        expect($recipient->unreadNotifications()->count())->toBe(1);
        expect($recipient->unreadNotifications()->first()->data['url'])->toBe('/orders/'.$order->id);
    }
    expect($otherSeller->unreadNotifications()->count())->toBe(0);
});

test('customer dashboard shows available arrivals with working shopping links', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Customer dashboard arrival']);
    Product::factory()->create(['name' => 'Hidden draft arrival', 'status' => 'draft']);

    $this->actingAs($customer)->get('/dashboard')
        ->assertOk()
        ->assertSee('Brand New')
        ->assertSee('New Arrivals')
        ->assertSee($product->name)
        ->assertSee(route('catalog.show', $product), false)
        ->assertSee(route('cart.set'), false)
        ->assertDontSee('Hidden draft arrival');
});

test('seller dashboard scopes inventory sales and recent order items to its owner', function () {
    $product = Product::factory()->create(['name' => 'Owned collection product', 'stock' => 3]);
    $otherProduct = Product::factory()->create(['name' => 'Another seller collection']);
    $order = Order::factory()->create(['buyer_name' => 'Shared order customer']);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price_cents' => 1000, 'status' => 'delivered']);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $otherProduct->id, 'unit_price_cents' => 90000, 'status' => 'delivered']);
    $otherOrder = Order::factory()->create(['buyer_name' => 'Unrelated customer']);
    OrderItem::factory()->create(['order_id' => $otherOrder->id, 'product_id' => $otherProduct->id]);

    $this->actingAs($product->vendor)->get('/dashboard')
        ->assertOk()
        ->assertSee('New Possibilities.')
        ->assertSee($product->name)
        ->assertSee($order->buyer_name)
        ->assertDontSee($otherProduct->name)
        ->assertDontSee($otherOrder->buyer_name)
        ->assertViewHas('products', 1)
        ->assertViewHas('orders', 1)
        ->assertViewHas('sales', 2000)
        ->assertViewHas('extraCount', 1)
        ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 1 && $orders->first()->items->count() === 1 && $orders->first()->items->first()->vendor_id === $product->vendor_id);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get('/dashboard')
        ->assertOk()
        ->assertSee('One Clear View.')
        ->assertSee($product->name)
        ->assertSee($otherProduct->name)
        ->assertSee($otherOrder->buyer_name)
        ->assertViewHas('products', 2)
        ->assertViewHas('orders', 2)
        ->assertViewHas('sales', 92000)
        ->assertViewHas('extraCount', User::count());
});

test('catalog prioritizes hoodies and shirts over newer unrelated products', function () {
    $hoodie = Product::factory()->create(['name' => 'Classic Hoodie']);
    $shirt = Product::factory()->create(['name' => 'Cotton Shirt']);
    $shoes = Product::factory()->create(['name' => 'Running Shoes']);
    $this->getJson('/api/v1/products')->assertOk()->assertJsonPath('data.0.id', $hoodie->id)->assertJsonPath('data.1.id', $shirt->id)->assertJsonPath('data.2.id', $shoes->id);
});
