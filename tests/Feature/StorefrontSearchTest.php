<?php

namespace Tests\Feature;

use App\Modules\Inventories\Brands\Models\Brand;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Inventories\Products\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_product_details_brand_category_and_variant_sku(): void
    {
        $category = Category::create(['name' => 'Water Purifier', 'slug' => 'water-purifier', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Aqua Bangla', 'slug' => 'aqua-bangla', 'is_active' => true]);
        $product = Product::create([
            'title' => 'Premium Filter', 'slug' => 'premium-filter', 'sku' => 'FILTER-100',
            'description' => '<p>বিশুদ্ধ পানির উন্নত সমাধান</p>', 'tags' => 'home drinking',
            'regular_price' => 1200, 'stock_quantity' => 5, 'status' => 'Published',
            'visibility' => 'Public', 'brand_id' => $brand->id,
        ]);
        $product->categories()->attach($category);
        ProductVariant::create(['product_id' => $product->id, 'name' => 'Blue Large', 'sku' => 'BLUE-XL-77', 'regular_price' => 1200, 'stock_quantity' => 2, 'is_active' => true]);
        Product::create(['title' => 'Hidden Water Product', 'slug' => 'hidden-water-product', 'regular_price' => 500, 'status' => 'Draft', 'visibility' => 'Public']);

        foreach (['Premium', 'FILTER-100', 'বিশুদ্ধ পানি', 'Aqua Bangla', 'Water Purifier', 'BLUE-XL-77'] as $search) {
            $this->get(route('storefront.products.index', ['search' => $search]))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('app/modules/storefront/products/pages/Index', false)
                    ->has('products.data', 1)
                    ->where('products.data.0.title', 'Premium Filter')
                    ->where('filters.search', $search));
        }
    }

    public function test_search_keeps_only_public_published_products_and_normalizes_the_query(): void
    {
        Product::create(['title' => 'Visible Pump', 'slug' => 'visible-pump', 'regular_price' => 500, 'status' => 'Published', 'visibility' => 'Public']);
        Product::create(['title' => 'Private Pump', 'slug' => 'private-pump', 'regular_price' => 500, 'status' => 'Published', 'visibility' => 'Private']);

        $this->get(route('storefront.products.index', ['search' => '  Visible   Pump  ']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.title', 'Visible Pump')
                ->where('filters.search', 'Visible Pump'));
    }
}
