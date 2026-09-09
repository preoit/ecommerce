<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->boolean('delivery_enabled')->default(true);
            $table->decimal('delivery_inside_dhaka', 10, 2)->default(80);
            $table->decimal('delivery_outside_dhaka', 10, 2)->default(150);
            $table->boolean('free_delivery_enabled')->default(false);
            $table->decimal('free_delivery_threshold', 12, 2)->nullable();
            $table->boolean('cod_surcharge_enabled')->default(false);
            $table->decimal('cod_surcharge', 10, 2)->default(0);
            $table->boolean('product_delivery_override_enabled')->default(true);
            $table->boolean('heavy_delivery_enabled')->default(false);
            $table->decimal('heavy_weight_threshold', 10, 3)->default(5);
            $table->decimal('heavy_charge_per_kg', 10, 2)->default(0);
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_zone', 30)->nullable()->after('city');
            $table->decimal('cod_surcharge', 10, 2)->default(0)->after('shipping_total');
            $table->json('delivery_breakdown')->nullable()->after('cod_surcharge');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['delivery_zone', 'cod_surcharge', 'delivery_breakdown']));
        Schema::table('website_settings', fn (Blueprint $table) => $table->dropColumn(['delivery_enabled', 'delivery_inside_dhaka', 'delivery_outside_dhaka', 'free_delivery_enabled', 'free_delivery_threshold', 'cod_surcharge_enabled', 'cod_surcharge', 'product_delivery_override_enabled', 'heavy_delivery_enabled', 'heavy_weight_threshold', 'heavy_charge_per_kg']));
    }
};