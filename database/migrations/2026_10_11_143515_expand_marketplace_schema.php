<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
        });
        DB::table('users')->where('role', 'staff')->update(['role' => 'vendor']);
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique();
        });
        DB::table('categories')->orderBy('id')->each(function ($category): void {
            DB::table('categories')->where('id', $category->id)->update(['slug' => (Str::slug($category->name) ?: 'category').'-'.$category->id]);
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('vendor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('status')->default('active')->index();
            $table->softDeletes();
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->change();
            $table->foreignId('product_id')->nullable()->change();
            $table->unsignedInteger('quantity')->nullable()->change();
            $table->decimal('unit_price', 12, 2)->nullable()->change();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable();
            $table->text('shipping_address')->nullable();
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->string('idempotency_key', 64)->nullable();
            $table->string('request_hash', 64)->nullable();
            $table->unique(['user_id', 'idempotency_key']);
            $table->timestamp('cancelled_at')->nullable();
        });
        DB::table('orders')->orderBy('id')->each(function ($order): void {
            $customer = DB::table('customers')->find($order->customer_id);
            $user = $customer ? DB::table('users')->where('email', $customer->email)->first() : null;
            $total = (int) round((float) $order->unit_price * 100) * $order->quantity;
            DB::table('orders')->where('id', $order->id)->update(['user_id' => $user?->id, 'buyer_name' => $customer?->name, 'buyer_email' => $customer?->email, 'shipping_address' => $customer?->address, 'subtotal_cents' => $total, 'total_cents' => $total]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'idempotency_key']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['buyer_name', 'buyer_email', 'shipping_address', 'subtotal_cents', 'discount_cents', 'tax_cents', 'total_cents', 'idempotency_key', 'request_hash', 'cancelled_at']);
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn(['status', 'deleted_at']);
        });
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('slug');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_path', 'active', 'deleted_at']);
        });
    }
};
