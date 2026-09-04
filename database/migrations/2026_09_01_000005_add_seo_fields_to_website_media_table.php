<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_media', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('name');
            $table->string('alt_text')->nullable()->after('title');
            $table->text('caption')->nullable()->after('alt_text');
        });
    }

    public function down(): void
    {
        Schema::table('website_media', function (Blueprint $table): void {
            $table->dropColumn(['title', 'alt_text', 'caption']);
        });
    }
};
