<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('active')->group(function (): void {
    Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/products/{product}', [CatalogController::class, 'show'])->whereNumber('product')->name('catalog.show');
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart', [CartController::class, 'set'])->middleware('throttle:60,1')->name('cart.set');
});
Route::middleware('guest')->group(function (): void {
    Route::view('/forgot-password', 'password', ['reset' => false])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'send'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token) => view('password', ['reset' => true, 'token' => $token]))->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
    Route::view('/login', 'auth', ['register' => false])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::view('/register', 'auth', ['register' => true])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});
Route::middleware(['auth', 'active'])->group(function (): void {
    Route::view('/verify-email', 'verify-email')->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
    Route::post('/notifications/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::view('/account', 'account')->name('account');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->middleware(['role:customer', 'throttle:10,1'])->name('orders.store');
    Route::patch('/orders/{order}/tracking', [OrderController::class, 'tracking'])->middleware('role:admin,vendor')->name('orders.tracking');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}', [OrderController::class, 'update'])->middleware('role:admin,vendor')->name('orders.update');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->middleware('role:admin')->name('orders.destroy');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    Route::get('/orders/{order}/pdf', [OrderController::class, 'pdf'])->name('orders.pdf');
    Route::post('/orders/{order}/email', [OrderController::class, 'email'])->middleware('throttle:10,1')->name('orders.email');
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->middleware(['role:customer', 'throttle:20,1'])->name('reviews.store');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::middleware('role:admin,vendor')->prefix('manage/{entity}')->where(['entity' => 'users|categories|products|coupons'])->group(function (): void {
        Route::get('/', [ManagementController::class, 'index'])->name('records.index');
        Route::get('/create', [ManagementController::class, 'form'])->name('records.create');
        Route::post('/', [ManagementController::class, 'save'])->name('records.store');
        Route::get('/{id}/edit', [ManagementController::class, 'form'])->whereNumber('id')->name('records.edit');
        Route::get('/{id}', [ManagementController::class, 'show'])->whereNumber('id')->name('records.show');
        Route::put('/{id}', [ManagementController::class, 'save'])->whereNumber('id')->name('records.update');
        Route::delete('/{id}', [ManagementController::class, 'destroy'])->whereNumber('id')->name('records.destroy');
    });
    Route::view('/settings', 'settings')->middleware('role:admin')->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('role:admin')->name('settings.update');
    Route::view('/api-guide', 'api-guide')->name('api.guide');
});
