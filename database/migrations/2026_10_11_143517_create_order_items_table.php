<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_cents');
            $table->string('status')->default('pending');
            $table->boolean('inventory_reserved')->default(true);
            $table->timestamps();
            $table->index(['vendor_id', 'status']);
        });
        DB::table('orders')->orderBy('id')->each(function ($order): void {
            $product = DB::table('products')->find($order->product_id);
            if ($product) {
                DB::table('order_items')->insert(['order_id' => $order->id, 'product_id' => $product->id, 'vendor_id' => $product->vendor_id, 'product_name' => $product->name, 'quantity' => $order->quantity, 'unit_price_cents' => (int) round((float) $order->unit_price * 100), 'status' => $order->status === 'shipped' ? 'shipped' : ($order->status === 'cancelled' ? 'cancelled' : 'pending'), 'inventory_reserved' => false, 'created_at' => $order->created_at, 'updated_at' => $order->updated_at]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
