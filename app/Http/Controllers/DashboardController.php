<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        if ($user->role === 'customer') {
            $arrivals = Product::available()->with(['category', 'vendor', 'variants'])->withAvg('reviews', 'rating')->fashionOrder()->limit(4)->get();

            return view('dashboard-customer', compact('arrivals'));
        }
        $products = $user->isVendor() ? $user->products()->count() : Product::count();
        $orders = Order::visibleTo($user)->count();
        $salesQuery = OrderItem::where('status', 'delivered');
        if ($user->isVendor()) {
            $salesQuery->where('vendor_id', $user->id);
        }
        $sales = $salesQuery->selectRaw('COALESCE(SUM(quantity * unit_price_cents),0) as total')->value('total');
        $collectionQuery = Product::with('category');
        if ($user->isVendor()) {
            $collectionQuery->where('vendor_id', $user->id);
        }
        $collection = $collectionQuery->latest()->orderByDesc('id')->limit(4)->get();
        $recentOrders = Order::visibleTo($user)->with(['items' => fn ($query) => $user->isVendor() ? $query->where('vendor_id', $user->id) : $query])->latest()->orderByDesc('id')->limit(5)->get();
        $extraCount = $user->isAdmin() ? User::count() : $user->products()->where('stock', '<=', (int) Setting::read('low_stock_threshold', '5'))->count();

        return view('dashboard', compact('products', 'orders', 'sales', 'collection', 'recentOrders', 'extraCount'));
    }
}
