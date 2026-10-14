<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function (): void {
    Route::post('/tokens', [ApiTokenController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/products', [CatalogController::class, 'index']);
    Route::get('/products/{product}', [CatalogController::class, 'show'])->whereNumber('product');
    Route::middleware(['auth:sanctum', 'active', 'abilities:commerce'])->group(function (): void {
        Route::delete('/tokens/current', [ApiTokenController::class, 'destroy']);
        Route::get('/products/{product}/inventory', [CatalogController::class, 'inventory'])->whereNumber('product');
        Route::get('/cart', [CartController::class, 'show'])->middleware('role:customer');
        Route::put('/cart', [CartController::class, 'sync'])->middleware('role:customer');
        Route::post('/cart/items', [CartController::class, 'set'])->middleware('role:customer');
        Route::get('/orders', [OrderController::class, 'index'])->middleware('role:customer,vendor');
        Route::post('/orders', [OrderController::class, 'store'])->middleware(['role:customer', 'throttle:10,1']);
        Route::patch('/orders/{order}/tracking', [OrderController::class, 'tracking'])->middleware('role:vendor');
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::patch('/orders/{order}', [OrderController::class, 'update'])->middleware('role:vendor');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    });
});
