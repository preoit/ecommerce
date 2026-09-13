<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blog_authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('designation')->nullable();
            $table->text('bio')->nullable();
            $table->string('image_path', 2048)->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('blog_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path', 2048)->nullable();
            $table->timestamps();
        });
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('blog_authors')->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('image_path', 2048)->nullable();
            $table->json('gallery')->nullable();
            $table->text('tags')->nullable();
            $table->string('status')->default('Draft');
            $table->timestamp('published_at')->nullable();
            $table->json('seo')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });
        Schema::create('blog_category_post', function (Blueprint $table) {
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_category_id')->constrained()->restrictOnDelete();
            $table->primary(['blog_post_id', 'blog_category_id']);
        });
        Schema::create('blog_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_redirects');
        Schema::dropIfExists('blog_category_post');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('blog_authors');
    }
};
