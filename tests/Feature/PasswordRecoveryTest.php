<?php

use App\Models\User;
use App\Notifications\PasswordResetLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);
test('active users can reset their password once and api tokens are revoked', function () {
    Notification::fake();
    $user = User::factory()->create();
    $user->createToken('old');
    $this->get('/forgot-password')->assertOk();
    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors()->assertRedirect();
    $token = null;
    Notification::assertSentTo($user, PasswordResetLink::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });
    $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))->assertOk();
    $data = ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword12345', 'password_confirmation' => 'NewPassword12345'];
    $this->post('/reset-password', $data)->assertRedirect('/login')->assertSessionHasNoErrors();
    expect(Hash::check('NewPassword12345', $user->fresh()->password))->toBeTrue();
    expect($user->tokens()->count())->toBe(0);
    $this->post('/reset-password', $data)->assertSessionHasErrors('email');
});
test('recovery does not reveal unknown accounts and does not notify disabled accounts', function () {
    Notification::fake();
    $user = User::factory()->create(['active' => false]);
    $this->post('/forgot-password', ['email' => 'unknown@example.com'])->assertSessionHas('success');
    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success');
    Notification::assertNothingSent();
});
