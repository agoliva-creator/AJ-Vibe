<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('commerce.admin_email');
        $password = config('commerce.admin_password');
        if ($email && $password) {
            Validator::make(['email' => $email, 'password' => $password], ['email' => 'required|email', 'password' => ['required', Password::min(12)->mixedCase()->numbers()]])->validate();
            $admin = User::firstOrNew(['email' => $email]);
            if (! $admin->exists) {
                $admin->fill(['name' => 'Administrator', 'password' => $password]);
                $admin->role = 'admin';
                $admin->active = true;
                $admin->save();
            } elseif (! $admin->isAdmin() || ! $admin->active) {
                throw new \RuntimeException('ADMIN_EMAIL must identify an active admin or a new account.');
            }
        }
        foreach (['store_name' => 'Commerce Desk', 'tax_rate_bps' => '0', 'low_stock_threshold' => '5', 'shipping_fee_cents' => '10000'] as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
        if (app()->environment('local', 'testing') && config('commerce.demo')) {
            foreach (['admin', 'vendor', 'customer'] as $role) {
                $user = User::firstOrNew(['email' => $role.'@commerce.test']);
                if (! $user->exists) {
                    $user->name = ucfirst($role).' Demo';
                    $user->password = 'CommerceDemo123!';
                    $user->role = $role;
                    $user->active = true;
                    $user->save();
                }
            }
            $vendor = User::where('email', 'vendor@commerce.test')->firstOrFail();
            $category = Category::firstOrCreate(['slug' => 'essentials'], ['name' => 'Essentials', 'description' => 'Useful everyday favorites.']);
            foreach (['Everyday tote' => '499.00', 'Travel organizer' => '349.00', 'Desk notebook' => '199.00'] as $name => $price) {
                Product::firstOrCreate(['name' => $name, 'vendor_id' => $vendor->id], ['category_id' => $category->id, 'price' => $price, 'stock' => 25, 'status' => 'active', 'description' => 'A thoughtfully designed everyday essential.']);
            }
            Coupon::firstOrCreate(['code' => 'WELCOME10'], ['type' => 'percent', 'value' => 10, 'minimum_cents' => 0, 'active' => true]);
        }
        if (! User::where('role', 'admin')->where('active', true)->exists()) {
            throw new \RuntimeException('Set ADMIN_EMAIL and a strong ADMIN_PASSWORD before seeding. Demo accounts require SEED_DEMO=true and a local environment.');
        }
    }
}
