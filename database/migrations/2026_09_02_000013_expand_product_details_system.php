<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->unique()->after('sku');
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('warranty')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('key_features')->nullable();
            $table->longText('key_benefits')->nullable();
            $table->longText('box_contents')->nullable();
            $table->longText('how_to_use')->nullable();
            $table->longText('suitable_for')->nullable();
            $table->longText('care_instructions')->nullable();
            $table->string('video_url')->nullable();
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->unsignedInteger('min_order_quantity')->default(1);
            $table->unsignedInteger('max_order_quantity')->nullable();
            $table->unsignedInteger('quantity_step')->default(1);
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_new_arrival')->default(false)->index();
            $table->boolean('is_best_seller')->default(false)->index();
            $table->decimal('delivery_inside_dhaka', 10, 2)->nullable();
            $table->decimal('delivery_outside_dhaka', 10, 2)->nullable();
            $table->string('estimated_delivery')->nullable();
            $table->boolean('cash_on_delivery')->default(true);
            $table->text('advance_payment')->nullable();
            $table->text('return_policy')->nullable();
            $table->text('replacement_policy')->nullable();
            $table->string('payment_methods')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('meta_robots')->default('index,follow')->index();
            $table->string('focus_keyword')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('twitter_image_path')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->softDeletes();
            $table->index(['status', 'visibility', 'published_at']);
        });

        Schema::create('product_images', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->string('path'); $table->string('alt_text')->nullable(); $table->string('title')->nullable(); $table->unsignedInteger('width')->nullable(); $table->unsignedInteger('height')->nullable(); $table->unsignedInteger('sort_order')->default(0); $table->timestamps(); $table->index(['product_id','sort_order']); });
        Schema::create('product_specifications', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->string('name'); $table->text('value'); $table->unsignedInteger('sort_order')->default(0); $table->timestamps(); $table->index(['product_id','sort_order']); });
        Schema::create('product_reviews', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->unsignedTinyInteger('rating'); $table->string('title')->nullable(); $table->text('description'); $table->string('customer_name'); $table->string('customer_email')->nullable(); $table->string('video_url')->nullable(); $table->boolean('verified_purchase')->default(false); $table->unsignedInteger('helpful_count')->default(0); $table->text('admin_reply')->nullable(); $table->enum('status',['pending','approved','rejected','spam'])->default('pending')->index(); $table->timestamps(); $table->index(['product_id','status']); });
        Schema::create('product_review_images', function (Blueprint $table) { $table->id(); $table->foreignId('review_id')->constrained('product_reviews')->cascadeOnDelete(); $table->string('path'); $table->timestamps(); });
        Schema::create('product_review_votes', function (Blueprint $table) { $table->id(); $table->foreignId('review_id')->constrained('product_reviews')->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); $table->string('session_id')->nullable(); $table->timestamps(); $table->unique(['review_id','user_id']); $table->index(['review_id','session_id']); });
        Schema::create('product_questions', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('customer_name'); $table->string('customer_email')->nullable(); $table->text('question'); $table->text('answer')->nullable(); $table->timestamp('answered_at')->nullable(); $table->enum('status',['pending','approved','rejected','spam'])->default('pending')->index(); $table->timestamps(); $table->index(['product_id','status']); });
        Schema::create('product_faqs', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->string('question'); $table->text('answer'); $table->unsignedInteger('sort_order')->default(0); $table->boolean('is_active')->default(true); $table->timestamps(); $table->index(['product_id','is_active','sort_order']); });
        Schema::create('wishlists', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->timestamps(); $table->unique(['user_id','product_id']); });
        Schema::create('compare_products', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); $table->string('session_id')->nullable(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->timestamps(); $table->index(['session_id','product_id']); });
        Schema::create('recently_viewed_products', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); $table->string('session_id')->nullable(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->timestamp('viewed_at'); $table->index(['user_id','viewed_at']); $table->index(['session_id','viewed_at']); });
        Schema::create('stock_notifications', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('email')->nullable(); $table->string('phone', 20)->nullable(); $table->timestamp('notified_at')->nullable(); $table->timestamps(); $table->index(['product_id','notified_at']); });
        Schema::create('product_badges', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('background_color')->default('#7c3aed'); $table->string('text_color')->default('#ffffff'); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('product_badge', function (Blueprint $table) { $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_badge_id')->constrained()->cascadeOnDelete(); $table->primary(['product_id','product_badge_id']); });
        Schema::create('related_products', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete(); $table->enum('type',['related','similar','frequently_bought','also_viewed'])->default('related'); $table->unsignedInteger('sort_order')->default(0); $table->unique(['product_id','related_product_id','type']); });
        Schema::create('product_bulk_prices', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->unsignedInteger('min_quantity'); $table->unsignedInteger('max_quantity')->nullable(); $table->decimal('unit_price', 12, 2); $table->timestamps(); $table->index(['product_id','min_quantity']); });
        Schema::create('product_bundles', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->string('title'); $table->decimal('discount_amount', 12, 2)->default(0); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('product_bundle_items', function (Blueprint $table) { $table->id(); $table->foreignId('bundle_id')->constrained('product_bundles')->cascadeOnDelete(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->unsignedInteger('quantity')->default(1); $table->unique(['bundle_id','product_id']); });
        Schema::create('product_url_redirects', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->string('old_slug')->unique(); $table->timestamps(); });
    }

    public function down(): void
    {
        foreach (['product_url_redirects','product_bundle_items','product_bundles','product_bulk_prices','related_products','product_badge','product_badges','stock_notifications','recently_viewed_products','compare_products','wishlists','product_faqs','product_questions','product_review_votes','product_review_images','product_reviews','product_specifications','product_images'] as $table) Schema::dropIfExists($table);
        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['barcode','model','manufacturer','country_of_origin','weight','length','width','height','warranty','short_description','key_features','key_benefits','box_contents','how_to_use','suitable_for','care_instructions','video_url','low_stock_threshold','min_order_quantity','max_order_quantity','quantity_step','is_featured','is_new_arrival','is_best_seller','delivery_inside_dhaka','delivery_outside_dhaka','estimated_delivery','cash_on_delivery','advance_payment','return_policy','replacement_policy','payment_methods','canonical_url','meta_robots','focus_keyword','og_title','og_description','og_image_path','twitter_image_path','published_at']);
        });
    }
};
