<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('orders', function (Blueprint $table): void { $table->string('source', 30)->default('storefront')->after('order_number'); $table->decimal('discount_total', 12, 2)->default(0)->after('subtotal'); }); }
    public function down(): void { Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['source', 'discount_total'])); }
};
