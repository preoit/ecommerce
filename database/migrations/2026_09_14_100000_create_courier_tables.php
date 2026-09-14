<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('slug')->unique(); $t->string('logo')->nullable();
            $t->boolean('active')->default(false); $t->boolean('sandbox_mode')->default(false);
            $t->string('api_url'); $t->text('credentials')->nullable(); $t->json('status_mapping')->nullable();
            $t->timestamp('tested_at')->nullable(); $t->timestamps();
        });
        Schema::create('courier_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('order_id')->constrained()->restrictOnDelete();
            $t->foreignId('courier_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('active_order_id')->nullable()->unique();
            $t->string('reference')->unique(); $t->string('courier_order_id')->nullable();
            $t->string('consignment_id')->nullable(); $t->string('tracking_code')->nullable();
            $t->string('status')->default('queued')->index(); $t->string('api_status')->nullable();
            $t->boolean('sandbox_mode')->default(false); $t->string('api_url');
            $t->decimal('cod_amount', 12, 2)->default(0); $t->decimal('delivery_charge', 12, 2)->nullable();
            $t->decimal('collected_cod', 12, 2)->nullable(); $t->text('request_data'); $t->longText('response_data')->nullable();
            $t->text('error')->nullable(); $t->timestamp('booked_at')->nullable()->index();
            $t->timestamp('delivered_at')->nullable(); $t->timestamp('synced_at')->nullable(); $t->timestamps();
            $t->unique(['courier_id', 'sandbox_mode', 'consignment_id']);
        });
        Schema::create('courier_status_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('courier_order_id')->constrained()->cascadeOnDelete();
            $t->string('status'); $t->string('api_status')->nullable(); $t->longText('response')->nullable(); $t->timestamp('created_at');
        });
        foreach (['steadfast' => ['Steadfast Courier', 'https://portal.packzy.com/api/v1'], 'pathao' => ['Pathao Courier', 'https://api-hermes.pathao.com']] as $slug => [$name, $url]) {
            DB::table('couriers')->insert(['slug'=>$slug,'name'=>$name,'api_url'=>$url,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void { Schema::dropIfExists('courier_status_logs'); Schema::dropIfExists('courier_orders'); Schema::dropIfExists('couriers'); }
};
