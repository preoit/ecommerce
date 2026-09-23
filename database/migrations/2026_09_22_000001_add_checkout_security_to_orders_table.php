<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->uuid('checkout_token')->nullable()->unique()->after('order_number');
            $table->string('public_token', 64)->nullable()->unique()->after('checkout_token');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['checkout_token']);
            $table->dropUnique(['public_token']);
            $table->dropColumn(['checkout_token', 'public_token']);
        });
    }
};
