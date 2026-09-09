<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('password')->index();
        });
        DB::table('users')->update(['is_admin' => true]);
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->default('Home');
            $table->string('recipient_name', 120);
            $table->string('phone', 30);
            $table->string('delivery_zone', 30);
            $table->string('district', 120);
            $table->string('city', 120);
            $table->string('area', 120)->nullable();
            $table->text('address');
            $table->string('postal_code', 30)->nullable();
            $table->string('landmark', 255)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('customer_address_id')->nullable()->after('user_id')->constrained('customer_addresses')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_address_id'));
        Schema::dropIfExists('customer_addresses');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};