<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->string('order_whatsapp', 20)->nullable()->default('8801736741793');
            $table->string('order_phone', 20)->nullable()->default('8801736741793');
        });
    }
    public function down(): void
    {
        Schema::table('website_settings', fn (Blueprint $table) => $table->dropColumn(['order_whatsapp', 'order_phone']));
    }
};
