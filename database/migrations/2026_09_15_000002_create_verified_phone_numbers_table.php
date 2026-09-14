<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verified_phone_numbers', function (Blueprint $table): void {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->timestamp('verified_at');
            $table->timestamps();
        });

        if (Schema::hasColumn('orders', 'phone_verified_at')) {
            DB::table('orders')->whereNotNull('phone_verified_at')->orderBy('id')->get(['phone', 'phone_verified_at'])->each(function (object $order): void {
                $phone = preg_replace('/\D+/', '', (string) $order->phone);
                if (str_starts_with($phone, '880')) $phone = '0'.substr($phone, 3);
                if (! preg_match('/^01[3-9]\d{8}$/', $phone)) return;
                DB::table('verified_phone_numbers')->updateOrInsert(
                    ['phone' => $phone],
                    ['verified_at' => $order->phone_verified_at, 'created_at' => now(), 'updated_at' => now()],
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('verified_phone_numbers');
    }
};
