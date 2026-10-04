<?php

namespace Tests\Feature;

use App\Modules\Inventories\Brands\Models\Brand;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StorefrontSearchSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggestions_return_public_matches_from_one_product_query(): void
    {
        $brand = Brand::create(['name' => 'Aqua Pure', 'slug' => 'aqua-pure', 'is_active' => true]);
        $category = Category::create(['name' => 'Water Filter', 'slug' => 'water-filter', 'is_active' => true]);
        $product = Product::create(['title' => 'Premium Purifier', 'slug' => 'premium-purifier', 'sku' => 'AQUA-100', 'regular_price' => 1000, 'sale_price' => 900, 'status' => 'Published', 'visibility' => 'Public', 'brand_id' => $brand->id]);
        $product->categories()->attach($category);
        Product::create(['title' => 'Private Aqua', 'slug' => 'private-aqua', 'regular_price' => 500, 'status' => 'Published', 'visibility' => 'Private']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('storefront.products.suggestions', ['q' => 'Aqua']))
            ->assertOk()
            ->assertJsonCount(1, 'suggestions')
            ->assertJsonPath('suggestions.0.title', 'Premium Purifier')
            ->assertJsonPath('suggestions.0.price', 900);

        $productQueries = collect(DB::getQueryLog())->filter(fn (array $query) => str_contains(strtolower($query['query']), 'from "products"'));
        $this->assertCount(1, $productQueries);
    }

    public function test_short_terms_skip_the_database_and_repeated_terms_use_cache(): void
    {
        Product::create(['title' => 'Filter Machine', 'slug' => 'filter-machine', 'regular_price' => 500, 'status' => 'Published', 'visibility' => 'Public']);
        Cache::flush();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('storefront.products.suggestions', ['q' => 'F']))->assertOk()->assertJsonCount(0, 'suggestions');
        $this->assertCount(0, DB::getQueryLog());

        $this->getJson(route('storefront.products.suggestions', ['q' => 'Filter']))->assertOk()->assertJsonCount(1, 'suggestions');
        $queriesAfterFirstSearch = count(DB::getQueryLog());
        $this->getJson(route('storefront.products.suggestions', ['q' => 'Filter']))->assertOk()->assertJsonCount(1, 'suggestions');
        $this->assertSame($queriesAfterFirstSearch, count(DB::getQueryLog()));
    }
}
