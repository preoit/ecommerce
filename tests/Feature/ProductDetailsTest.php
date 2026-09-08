<?php

namespace Tests\Feature;

use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Settings\Models\WebsiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_store_only_lists_published_public_products(): void
    {
        Product::create(['title'=>'Public Product','slug'=>'public-product','regular_price'=>100,'stock_quantity'=>5,'status'=>'Published','visibility'=>'Public']);
        Product::create(['title'=>'Draft Product','slug'=>'draft-product','regular_price'=>100,'stock_quantity'=>5,'status'=>'Draft','visibility'=>'Public']);
        Product::create(['title'=>'Private Product','slug'=>'private-product','regular_price'=>100,'stock_quantity'=>5,'status'=>'Published','visibility'=>'Private']);

        $this->get(route('storefront.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/modules/storefront/products/pages/Index', false)
                ->has('products.data', 1)
                ->where('products.data.0.title', 'Public Product'));
    }

    public function test_published_public_product_page_is_available(): void
    {
        $product = Product::create(['title'=>'Aqua Pro Filter','slug'=>'aqua-pro-filter','regular_price'=>100,'sale_price'=>90,'stock_quantity'=>10,'low_stock_threshold'=>3,'min_order_quantity'=>1,'quantity_step'=>1,'status'=>'Published','visibility'=>'Public','meta_robots'=>'index,follow','gallery'=>['website/media/gallery-one.webp','website/media/gallery-two.webp']]);
        $this->get(route('storefront.products.show',$product->slug))->assertOk()->assertInertia(fn($page)=>$page->component('app/modules/storefront/products/pages/Show', false)->where('product.discount_percentage',10)->where('product.gallery_urls.0','/image/gallery-one.webp?v=1')->where('product.gallery_urls.1','/image/gallery-two.webp?v=1'));
    }

    public function test_product_breadcrumb_contains_the_complete_category_hierarchy(): void
    {
        $parent = Category::create(['name' => 'Water Purifier', 'slug' => 'water-purifier', 'is_active' => true]);
        $child = Category::create(['name' => 'Filter Cartridge', 'slug' => 'filter-cartridge', 'parent_id' => $parent->id, 'is_active' => true]);
        $leaf = Category::create(['name' => 'PP Filter', 'slug' => 'pp-filter', 'parent_id' => $child->id, 'is_active' => true]);
        $product = Product::create(['title' => 'Six Piece Filter', 'slug' => 'six-piece-filter', 'regular_price' => 100, 'stock_quantity' => 1, 'status' => 'Published', 'visibility' => 'Public']);
        $product->categories()->attach($leaf);

        $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('product.category_breadcrumb.0.name', 'Water Purifier')
                ->where('product.category_breadcrumb.1.name', 'Filter Cartridge')
                ->where('product.category_breadcrumb.2.name', 'PP Filter'));
    }

    public function test_legacy_product_url_redirects_to_the_direct_url(): void
    {
        $product = Product::create(['title'=>'Direct URL','slug'=>'direct-url','regular_price'=>100,'stock_quantity'=>1,'status'=>'Published','visibility'=>'Public']);

        $this->get('/product/'.$product->slug)
            ->assertRedirect(route('storefront.products.show', $product->slug))
            ->assertStatus(301);
    }

    public function test_draft_product_is_not_public(): void
    {
        $product = Product::create(['title'=>'Draft','slug'=>'draft','regular_price'=>100,'stock_quantity'=>1,'status'=>'Draft','visibility'=>'Public']);
        $this->get(route('storefront.products.show',$product->slug))->assertNotFound();
    }

    public function test_cart_rejects_quantity_above_stock(): void
    {
        WebsiteSetting::create(['id' => 1, 'allow_out_of_stock_orders' => false, 'show_stock_to_customers' => true]);
        $product = Product::create(['title'=>'Stocked','slug'=>'stocked','regular_price'=>100,'stock_quantity'=>2,'low_stock_threshold'=>1,'min_order_quantity'=>1,'quantity_step'=>1,'status'=>'Published','visibility'=>'Public']);
        $this->postJson(route('storefront.products.cart',$product),['quantity'=>3])->assertUnprocessable();
    }

    public function test_out_of_stock_order_can_be_placed_and_alerts_admin_when_enabled(): void
    {
        WebsiteSetting::create(['id' => 1, 'allow_out_of_stock_orders' => true, 'show_stock_to_customers' => false]);
        $product = Product::create(['title'=>'Back order','slug'=>'back-order','regular_price'=>250,'stock_quantity'=>0,'min_order_quantity'=>1,'quantity_step'=>1,'status'=>'Published','visibility'=>'Public']);

        $this->postJson(route('storefront.products.cart', $product), ['quantity' => 2])->assertOk();
        $this->post(route('storefront.checkout.place-order'), [
            'customer_name' => 'Customer', 'phone' => '01700000000', 'address' => 'Dhaka', 'city' => 'Dhaka', 'payment_method' => 'cod',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['has_stock_shortage' => true]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2, 'stock_shortage_quantity' => 2]);
        $this->actingAs(User::factory()->create())->getJson(route('orders.notifications'))->assertOk()->assertJsonPath('orders.0.hasStockShortage', true);
        $this->assertSame(0, $product->fresh()->stock_quantity);
    }
    public function test_indexable_product_appears_in_sitemap(): void
    {
        Product::create(['title'=>'Indexed','slug'=>'indexed','regular_price'=>100,'stock_quantity'=>1,'status'=>'Published','visibility'=>'Public','meta_robots'=>'index,follow']);
        $this->get(route('sitemap.products'))->assertOk()->assertSee('/indexed');
    }

    public function test_product_edit_saves_featured_and_gallery_images(): void
    {
        $product = Product::create(['title'=>'Old title','slug'=>'old-title','regular_price'=>100,'stock_quantity'=>1,'status'=>'Draft','visibility'=>'Public']);

        $this->actingAs(User::factory()->create())->patch(route('inventories.products.update', $product), [
            'title' => 'Updated title',
            'regular' => 100,
            'sale' => null,
            'stock' => 2,
            'status' => 'Published',
            'visibility' => 'Public',
            'featuredImage' => ['path' => 'image/featured.webp'],
            'gallery' => [
                ['path' => 'image/gallery-one.webp'],
                ['path' => 'image/gallery-two.webp'],
            ],
        ])->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertSame('image/featured.webp', $product->featured_image_path);
        $this->assertSame(['image/gallery-one.webp', 'image/gallery-two.webp'], $product->gallery);
    }
}
