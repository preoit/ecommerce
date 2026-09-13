<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Inventories\Brands\Models\Brand;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Inventories\Units\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_sku_can_be_updated_kept_cleared_and_cannot_duplicate(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::create(['title' => 'SKU test', 'slug' => 'sku-test', 'sku' => 'OLD-SKU', 'regular_price' => 100, 'stock_quantity' => 5, 'status' => 'Published', 'visibility' => 'Public']);
        Product::create(['title' => 'Other', 'slug' => 'other-sku', 'sku' => 'TAKEN-SKU', 'regular_price' => 100, 'stock_quantity' => 1, 'status' => 'Published', 'visibility' => 'Public']);
        $payload = ['title' => $product->title, 'slug' => $product->slug, 'regular' => 100, 'stock' => 5, 'status' => 'Published', 'visibility' => 'Public'];
        $url = route('inventories.products.update', $product);
        $this->patch($url, [...$payload, 'sku' => 'NEW-SKU'])->assertSessionHasNoErrors();
        $this->assertSame('NEW-SKU', $product->fresh()->sku);
        $this->patch($url, [...$payload, 'sku' => 'NEW-SKU'])->assertSessionHasNoErrors();
        $this->patch($url, $payload)->assertSessionHasNoErrors();
        $this->assertSame('NEW-SKU', $product->fresh()->sku);
        $this->patch($url, [...$payload, 'sku' => 'TAKEN-SKU'])->assertSessionHasErrors('sku');
        $this->assertSame('NEW-SKU', $product->fresh()->sku);
        $this->patch($url, [...$payload, 'sku' => ''])->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->sku);
    }

    public function test_authenticated_user_can_create_products_with_clean_unique_slugs(): void
    {
        $category = Category::query()->create(['name' => 'Filters', 'slug' => 'filters']);
        Brand::query()->create(['name' => 'Aqua Pro', 'slug' => 'aqua-pro']);
        Unit::query()->create(['name' => 'Piece', 'slug' => 'piece']);

        $payload = [
            'title' => 'PP Filter 10 Inch', 'slug' => null,
            'shortDescription' => 'A reliable sediment filter.', 'description' => '<p>Product description</p>',
            'regular' => '750', 'sale' => '680', 'sku' => 'PP-10', 'barcode' => null,
            'stock' => '20', 'lowStockThreshold' => '5', 'minOrder' => '1', 'maxOrder' => null,
            'quantityStep' => '1', 'unit' => 'Piece', 'brand' => 'Aqua Pro', 'category' => [$category->id],
            'status' => 'Published', 'visibility' => 'Public', 'featuredImage' => null, 'gallery' => [],
            'videoUrl' => null, 'model' => null, 'manufacturer' => null, 'countryOfOrigin' => null,
            'weight' => null, 'length' => null, 'width' => null, 'height' => null, 'warranty' => null,
            'keyFeatures' => null, 'keyBenefits' => null, 'boxContents' => null, 'howToUse' => null,
            'suitableFor' => null, 'careInstructions' => null, 'deliveryInsideDhaka' => null,
            'deliveryOutsideDhaka' => null, 'estimatedDelivery' => null, 'cashOnDelivery' => true,
            'advancePayment' => null, 'returnPolicy' => null, 'replacementPolicy' => null,
            'paymentMethods' => null, 'isFeatured' => false, 'isNewArrival' => false, 'isBestSeller' => false,
            'specifications' => [], 'seoTitle' => null, 'meta' => null, 'canonicalUrl' => null,
            'metaRobots' => 'index,follow', 'focusKeyword' => null, 'ogTitle' => null,
            'ogDescription' => null, 'ogImage' => null, 'twitterImage' => null, 'tags' => null,
        ];

        $this->actingAs(User::factory()->create())
            ->post(route('inventories.products.store'), $payload)
            ->assertRedirect(route('inventories.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Product published successfully.');

        $product = Product::query()->where('slug', 'pp-filter-10-inch')->firstOrFail();
        $this->assertSame('Published', $product->status);
        $this->assertSame('Aqua Pro', $product->brand?->name);
        $this->assertSame('Piece', $product->unit?->name);
        $this->assertTrue($product->categories()->whereKey($category->id)->exists());

        $payload['sku'] = 'PP-10-SECOND';

        $this->actingAs(User::factory()->create())
            ->post(route('inventories.products.store'), $payload)
            ->assertRedirect(route('inventories.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'title' => 'PP Filter 10 Inch',
            'slug' => 'pp-filter-10-inch-2',
        ]);
    }

    public function test_editing_a_product_slug_preserves_the_old_url_redirect(): void
    {
        $product = Product::query()->create([
            'title' => 'Six Piece PP Filter',
            'slug' => 'six-piece-pp-filter-d81ol',
            'regular_price' => 700,
            'stock_quantity' => 10,
            'status' => 'Published',
            'visibility' => 'Public',
        ]);

        $this->actingAs(User::factory()->create())
            ->patch(route('inventories.products.update', $product), [
                'title' => $product->title,
                'slug' => 'six-piece-pp-filter',
                'regular' => 700,
                'sale' => null,
                'stock' => 10,
                'status' => 'Published',
                'visibility' => 'Public',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('six-piece-pp-filter', $product->refresh()->slug);
        $this->assertDatabaseHas('product_url_redirects', [
            'product_id' => $product->id,
            'old_slug' => 'six-piece-pp-filter-d81ol',
        ]);
    }

    public function test_editing_a_product_updates_its_brand_category_and_unit(): void
    {
        $oldBrand = Brand::query()->create(['name' => 'Old Brand', 'slug' => 'old-brand']);
        $newBrand = Brand::query()->create(['name' => 'New Brand', 'slug' => 'new-brand']);
        $oldCategory = Category::query()->create(['name' => 'Old Category', 'slug' => 'old-category']);
        $newCategory = Category::query()->create(['name' => 'New Category', 'slug' => 'new-category']);
        $oldUnit = Unit::query()->create(['name' => 'Piece', 'slug' => 'piece']);
        $newUnit = Unit::query()->create(['name' => 'Box', 'slug' => 'box']);
        $product = Product::query()->create([
            'title' => 'Editable Product',
            'slug' => 'editable-product',
            'regular_price' => 500,
            'stock_quantity' => 10,
            'brand_id' => $oldBrand->id,
            'unit_id' => $oldUnit->id,
            'status' => 'Published',
            'visibility' => 'Public',
        ]);
        $product->categories()->attach($oldCategory);

        $this->actingAs(User::factory()->create())
            ->patch(route('inventories.products.update', $product), [
                'title' => $product->title,
                'slug' => $product->slug,
                'regular' => 500,
                'sale' => null,
                'stock' => 10,
                'brand' => $newBrand->name,
                'unit' => $newUnit->name,
                'category' => [$newCategory->id],
                'status' => 'Published',
                'visibility' => 'Public',
            ])
            ->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame($newBrand->id, $product->brand_id);
        $this->assertSame($newUnit->id, $product->unit_id);
        $this->assertEquals([$newCategory->id], $product->categories()->pluck('categories.id')->all());
    }
}
