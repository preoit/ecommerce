<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'brands'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('seo_title', 160)->nullable();
                $table->text('meta_description')->nullable();
                $table->string('focus_keyword')->nullable();
                $table->string('canonical_url')->nullable();
                $table->string('meta_robots', 30)->default('index,follow');
                $table->string('og_title', 160)->nullable();
                $table->text('og_description')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['categories', 'brands'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['seo_title', 'meta_description', 'focus_keyword', 'canonical_url', 'meta_robots', 'og_title', 'og_description']);
            });
        }
    }
};
