<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('shipping_cents')->default(0);
        });
        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('courier', 80)->nullable();
            $table->string('tracking_number', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['courier', 'tracking_number']);
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('shipping_cents');
        });
    }
};
