<?php

namespace Tests\Feature;

use App\Modules\Inventories\Brands\Models\Brand;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListingFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_and_brand_filters_use_sale_price_and_positive_stock(): void
    {
        $category = Category::create(['name' => 'Filters', 'slug' => 'filters', 'is_active' => true]);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $category->id, 'is_active' => true]);
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        foreach ([[1000, 450, 1], [450, null, 0], [600, null, 5]] as $i => [$regular, $sale, $stock]) {
            $product = Product::create(['title' => 'Product '.$i, 'slug' => 'product-'.$i, 'regular_price' => $regular, 'sale_price' => $sale, 'stock_quantity' => $stock, 'status' => 'Published', 'visibility' => 'Public', 'brand_id' => $brand->id]);
            $product->categories()->attach($child);
        }
        Product::create(['title' => 'Unrelated', 'slug' => 'unrelated', 'regular_price' => 450, 'stock_quantity' => 4, 'status' => 'Published', 'visibility' => 'Public']);
        foreach (['/filters', route('storefront.products.index')] as $url) {
            $this->get($url.'?brand=brand&min_price=450&max_price=450&in_stock=1')
                ->assertOk()->assertInertia(fn ($page) => $page
                    ->has('products.data', 1)->where('products.data.0.title', 'Product 0')
                    ->where('priceBounds.min', 450)->where('priceBounds.max', 600));
        }
    }

    public function test_invalid_and_empty_ranges_are_handled(): void
    {
        $url = route('storefront.products.index');
        $this->getJson($url.'?min_price=500&max_price=100')->assertUnprocessable()->assertJsonValidationErrors('max_price');
        $this->getJson($url.'?min_price=-1')->assertUnprocessable()->assertJsonValidationErrors('min_price');
        $this->get($url)->assertOk()->assertInertia(fn ($page) => $page->has('products.data', 0)->where('priceBounds.min', 0)->where('priceBounds.max', 0));
    }
}
