<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->string('hero_primary_image_path')->nullable();
            $table->string('hero_primary_link')->nullable();
            $table->string('hero_secondary_image_path')->nullable();
            $table->string('hero_secondary_link')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'hero_primary_image_path',
                'hero_primary_link',
                'hero_secondary_image_path',
                'hero_secondary_link',
            ]);
        });
    }
};
