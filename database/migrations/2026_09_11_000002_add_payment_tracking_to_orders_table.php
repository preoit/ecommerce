<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('payment_status_updated_at')->nullable()->after('payment_status');
            $table->foreignId('payment_status_updated_by')->nullable()->after('payment_status_updated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_status_updated_by');
            $table->dropColumn('payment_status_updated_at');
        });
    }
};
