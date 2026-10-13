<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'product_id']);
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->string('variant_key')->default('base');
            $table->unique(['user_id', 'product_id', 'variant_key']);
        });
        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->string('variant_label')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('variant_id');
            $table->dropColumn('variant_label');
        });
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'product_id', 'variant_key']);
            $table->dropConstrainedForeignId('variant_id');
            $table->dropColumn('variant_key');
            $table->unique(['user_id', 'product_id']);
        });
    }
};
