<?php

use App\Models\Product;
use App\Models\User;
use App\Notifications\VerifyEmailLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);
test('unverified customers can checkout and optionally verify their email', function () {
    Notification::fake();
    Mail::fake();
    $user = User::factory()->unverified()->create();
    $product = Product::factory()->create();
    $user->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
    $data = ['shipping_address' => '123 Test Street City', 'idempotency_key' => (string) Str::uuid()];
    $this->actingAs($user)->post('/orders', $data)->assertSessionHasNoErrors()->assertRedirect();
    $user->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
    $data['idempotency_key'] = (string) Str::uuid();
    $this->postJson('/api/v1/orders', $data)->assertCreated();
    expect($user->orders()->count())->toBe(2);
    $this->get('/verify-email')->assertOk();
    $this->post('/email/verification-notification')->assertRedirect();
    Notification::assertSentTo($user, VerifyEmailLink::class);
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);
    $this->get($url)->assertRedirect('/cart');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
test('verification refuses tampered signatures and links belonging to another account', function () {
    $owner = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $owner->id, 'hash' => sha1($owner->email)]);
    $this->actingAs($other)->get($url)->assertForbidden();
    $this->actingAs($owner)->get($url.'&tampered=1')->assertForbidden();
    expect($owner->fresh()->hasVerifiedEmail())->toBeFalse();
});
