<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_specifications', function (Blueprint $table): void {
            $table->string('group_title')->nullable()->after('product_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('product_specifications', function (Blueprint $table): void {
            $table->dropIndex(['group_title']);
            $table->dropColumn('group_title');
        });
    }
};
