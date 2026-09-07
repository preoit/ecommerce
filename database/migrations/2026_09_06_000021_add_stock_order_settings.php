<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->boolean('allow_out_of_stock_orders')->default(true);
            $table->boolean('show_stock_to_customers')->default(false);
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->boolean('has_stock_shortage')->default(false)->index();
        });
        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedInteger('stock_shortage_quantity')->default(0);
        });
    }
    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('stock_shortage_quantity'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('has_stock_shortage'));
        Schema::table('website_settings', fn (Blueprint $table) => $table->dropColumn(['allow_out_of_stock_orders', 'show_stock_to_customers']));
    }
};