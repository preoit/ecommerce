<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('orders', function (Blueprint $table): void { $table->index(['status', 'id'], 'orders_status_id_index'); $table->index('created_at', 'orders_created_at_index'); $table->index('phone', 'orders_phone_index'); }); }
    public function down(): void { Schema::table('orders', function (Blueprint $table): void { $table->dropIndex('orders_status_id_index'); $table->dropIndex('orders_created_at_index'); $table->dropIndex('orders_phone_index'); }); }
};
