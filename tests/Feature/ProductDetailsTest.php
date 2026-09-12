<?php

namespace Tests\Feature;

use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Inventories\Products\Models\ProductVariant;
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

    public function test_variant_price_and_stock_are_revalidated_when_order_is_placed(): void
    {
        WebsiteSetting::create(['id' => 1, 'allow_out_of_stock_orders' => false, 'show_stock_to_customers' => true]);
        $product = Product::create(['title' => 'Variant Product', 'slug' => 'variant-product', 'regular_price' => 999, 'stock_quantity' => 3, 'min_order_quantity' => 1, 'quantity_step' => 1, 'status' => 'Published', 'visibility' => 'Public']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'name' => 'Red / XL', 'attributes' => ['Color' => 'Red', 'Size' => 'XL'], 'sku' => 'VAR-RED-XL', 'regular_price' => 500, 'sale_price' => 450, 'stock_quantity' => 3, 'is_active' => true]);

        $this->postJson(route('storefront.products.cart', $product), ['quantity' => 2, 'variant_id' => $variant->id])->assertOk();
        $variant->update(['sale_price' => 400]);
        $this->post(route('storefront.checkout.place-order'), ['customer_name' => 'Customer', 'phone' => '01700000000', 'address' => 'Dhaka', 'city' => 'Dhaka', 'delivery_zone' => 'inside_dhaka', 'payment_method' => 'cod'])->assertRedirect();

        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'variant_name' => 'Red / XL', 'sku' => 'VAR-RED-XL', 'unit_price' => 400, 'quantity' => 2]);
        $this->assertSame(1, $variant->fresh()->stock_quantity);
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_product_with_variants_requires_a_selected_variant(): void
    {
        $product = Product::create(['title' => 'Options', 'slug' => 'options', 'regular_price' => 100, 'stock_quantity' => 2, 'status' => 'Published', 'visibility' => 'Public']);
        ProductVariant::create(['product_id' => $product->id, 'name' => 'Blue', 'sku' => 'VAR-BLUE', 'regular_price' => 120, 'stock_quantity' => 2, 'is_active' => true]);

        $this->postJson(route('storefront.products.cart', $product), ['quantity' => 1])->assertUnprocessable();
    }

    public function test_delivery_rules_are_recalculated_and_saved_with_the_order(): void
    {
        WebsiteSetting::create(['id' => 1, 'allow_out_of_stock_orders' => false, 'show_stock_to_customers' => true, 'delivery_enabled' => true, 'delivery_inside_dhaka' => 80, 'delivery_outside_dhaka' => 150, 'cod_surcharge_enabled' => true, 'cod_surcharge' => 20, 'heavy_delivery_enabled' => true, 'heavy_weight_threshold' => 2, 'heavy_charge_per_kg' => 25, 'product_delivery_override_enabled' => true]);
        $product = Product::create(['title' => 'Heavy Product', 'slug' => 'heavy-product', 'regular_price' => 500, 'stock_quantity' => 5, 'weight' => 3, 'delivery_outside_dhaka' => 200, 'status' => 'Published', 'visibility' => 'Public']);
        $this->postJson(route('storefront.products.cart', $product), ['quantity' => 1])->assertOk();
        $this->post(route('storefront.checkout.place-order'), ['customer_name' => 'Customer', 'phone' => '01700000000', 'address' => 'Address', 'city' => 'Gazipur', 'delivery_zone' => 'outside_dhaka', 'payment_method' => 'cod'])->assertRedirect();

        $this->assertDatabaseHas('orders', ['subtotal' => 500, 'shipping_total' => 225, 'cod_surcharge' => 20, 'total' => 745, 'delivery_zone' => 'outside_dhaka']);
    }

    public function test_free_delivery_threshold_waives_shipping_but_keeps_cod_charge(): void
    {
        WebsiteSetting::create(['id' => 1, 'allow_out_of_stock_orders' => false, 'delivery_enabled' => true, 'delivery_inside_dhaka' => 80, 'free_delivery_enabled' => true, 'free_delivery_threshold' => 400, 'cod_surcharge_enabled' => true, 'cod_surcharge' => 15]);
        $product = Product::create(['title' => 'Free Delivery Product', 'slug' => 'free-delivery-product', 'regular_price' => 500, 'stock_quantity' => 2, 'status' => 'Published', 'visibility' => 'Public']);
        $this->postJson(route('storefront.products.cart', $product), ['quantity' => 1])->assertOk();
        $this->post(route('storefront.checkout.place-order'), ['customer_name' => 'Customer', 'phone' => '01700000000', 'address' => 'Address', 'city' => 'Dhaka', 'delivery_zone' => 'inside_dhaka', 'payment_method' => 'cod'])->assertRedirect();

        $this->assertDatabaseHas('orders', ['subtotal' => 500, 'shipping_total' => 0, 'cod_surcharge' => 15, 'total' => 515]);
    }
    public function test_cart_drawer_summary_can_be_loaded_updated_and_cleared_without_a_page_reload(): void
    {
        WebsiteSetting::create(['id' => 1, 'allow_out_of_stock_orders' => false]);
        $product = Product::create([
            'title' => 'Drawer Product', 'slug' => 'drawer-product', 'regular_price' => 300,
            'stock_quantity' => 5, 'min_order_quantity' => 1, 'quantity_step' => 1,
            'status' => 'Published', 'visibility' => 'Public',
        ]);

        $this->postJson(route('storefront.products.cart', $product), ['quantity' => 1])->assertOk();
        $summary = $this->getJson(route('storefront.cart.summary'))
            ->assertOk()
            ->assertJsonPath('cartCount', 1)
            ->assertJsonPath('subtotal', 300)
            ->assertJsonPath('items.0.title', 'Drawer Product');

        $cartKey = $summary->json('items.0.cart_key');
        $this->patchJson(route('storefront.cart.update', $cartKey), ['quantity' => 2])
            ->assertOk()
            ->assertJsonPath('cartCount', 2)
            ->assertJsonPath('subtotal', 600);

        $this->deleteJson(route('storefront.cart.remove', $cartKey))
            ->assertOk()
            ->assertJsonPath('cartCount', 0)
            ->assertJsonCount(0, 'items');
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
            'customer_name' => 'Customer', 'phone' => '01700000000', 'address' => 'Dhaka', 'city' => 'Dhaka', 'delivery_zone' => 'inside_dhaka', 'payment_method' => 'cod',
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

    public function test_product_edit_saves_and_exposes_grouped_specifications_on_storefront(): void
    {
        $product = Product::create(['title' => 'Filter', 'slug' => 'filter', 'regular_price' => 100, 'stock_quantity' => 1, 'status' => 'Published', 'visibility' => 'Public']);

        $this->actingAs(User::factory()->create())->patch(route('inventories.products.update', $product), [
            'title' => 'Filter',
            'regular' => 100,
            'sale' => null,
            'stock' => 1,
            'status' => 'Published',
            'visibility' => 'Public',
            'specifications' => [
                ['group_title' => 'Filter Details', 'name' => 'Size', 'value' => '10 Inch'],
                ['group_title' => 'Filter Details', 'name' => 'Material', 'value' => 'PP'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_specifications', ['product_id' => $product->id, 'group_title' => 'Filter Details', 'name' => 'Size', 'value' => '10 Inch']);
        $this->get(route('storefront.products.show', $product->slug))->assertOk()->assertInertia(fn ($page) => $page
            ->where('product.specification_groups.0.title', 'Filter Details')
            ->where('product.specification_groups.0.items.0.name', 'Size')
            ->where('product.specification_groups.0.items.1.value', 'PP'));
    }

    public function test_product_edit_saves_tags(): void
    {
        $product = Product::create(['title' => 'Filter', 'slug' => 'tagged-filter', 'regular_price' => 100, 'stock_quantity' => 1, 'status' => 'Published', 'visibility' => 'Public']);
        $tags = implode(', ', array_map(fn ($number) => "water purifier filter keyword {$number}", range(1, 15)));

        $this->actingAs(User::factory()->create())->patch(route('inventories.products.update', $product), [
            'title' => 'Filter',
            'regular' => 100,
            'sale' => null,
            'stock' => 1,
            'status' => 'Published',
            'visibility' => 'Public',
            'tags' => $tags,
        ])->assertSessionHasNoErrors();

        $this->assertGreaterThan(255, strlen($tags));
        $this->assertSame($tags, $product->fresh()->tags);
    }
}
