<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cart_items', 'order_items'] as $name) {
            if (! Schema::hasColumn($name, 'variant_id')) {
                Schema::table($name, function (Blueprint $table): void {
                    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
                });
            }
        }

        if (! Schema::hasColumn('cart_items', 'variant_key')) {
            Schema::table('cart_items', function (Blueprint $table): void {
                $table->string('variant_key')->default('base');
            });
        }

        if (! Schema::hasIndex('cart_items', 'cart_items_user_id_product_id_variant_key_unique')) {
            Schema::table('cart_items', function (Blueprint $table): void {
                $table->unique(['user_id', 'product_id', 'variant_key']);
            });
        }

        if (Schema::hasIndex('cart_items', 'cart_items_user_id_product_id_unique')) {
            Schema::table('cart_items', function (Blueprint $table): void {
                $table->dropUnique(['user_id', 'product_id']);
            });
        }

        if (! Schema::hasColumn('order_items', 'variant_label')) {
            Schema::table('order_items', function (Blueprint $table): void {
                $table->string('variant_label')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('variant_id');
            $table->dropColumn('variant_label');
        });
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'product_id', 'variant_key']);
            $table->dropConstrainedForeignId('variant_id');
            $table->dropColumn('variant_key');
        });
    }
};
