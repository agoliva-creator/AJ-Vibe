<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        View::composer(['settings', 'orders.invoice-content'], function (\Illuminate\View\View $view): void {
            $settings = Setting::whereIn('key', ['store_name', 'tax_rate_bps', 'shipping_fee_cents', 'low_stock_threshold'])->pluck('value', 'key')->all() + ['store_name' => 'Commerce Desk', 'tax_rate_bps' => '0', 'shipping_fee_cents' => '10000', 'low_stock_threshold' => '5'];
            $view->with('settings', $settings)->with('storeName', $settings['store_name']);
        });
        foreach (['User', 'Product', 'Category', 'Coupon', 'Order', 'Review'] as $model) {
            Gate::policy('App\\Models\\'.$model, 'App\\Policies\\'.$model.'Policy');
        }
    }
}
