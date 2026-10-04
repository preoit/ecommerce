<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCategorySectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_only_offers_active_main_categories(): void
    {
        $main = Category::create(['name' => 'Water Filter', 'slug' => 'water-filter', 'is_active' => true]);
        Category::create(['name' => 'PP Filter', 'slug' => 'pp-filter', 'parent_id' => $main->id, 'is_active' => true]);
        Category::create(['name' => 'Hidden Main', 'slug' => 'hidden-main', 'is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('website-design.homepage-categories.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/modules/website-design/homepage-categories/pages/Edit', false)
                ->has('categories', 1)
                ->where('categories.0.name', 'Water Filter'));
    }

    public function test_admin_can_save_main_categories_in_the_requested_order(): void
    {
        $first = Category::create(['name' => 'Engine Oil', 'slug' => 'engine-oil', 'is_active' => true]);
        $second = Category::create(['name' => 'Water Filter', 'slug' => 'water-filter', 'is_active' => true]);

        $this->actingAs(User::factory()->create())
            ->patch(route('website-design.homepage-categories.update'), [
                'sections' => [
                    ['category_id' => $second->id, 'enabled' => true, 'limit' => 12, 'sort' => 'featured'],
                    ['category_id' => $first->id, 'enabled' => false, 'limit' => 4, 'sort' => 'latest'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Homepage category sections saved successfully.');

        $sections = WebsiteSetting::findOrFail(1)->homepage_category_sections;
        $this->assertSame([$second->id, $first->id], array_column($sections, 'category_id'));
        $this->assertSame(12, $sections[0]['limit']);
        $this->assertSame('featured', $sections[0]['sort']);
        $this->assertFalse($sections[1]['enabled']);
    }

    public function test_subcategory_cannot_be_saved_as_a_homepage_section(): void
    {
        $main = Category::create(['name' => 'Water Filter', 'slug' => 'water-filter', 'is_active' => true]);
        $child = Category::create(['name' => 'PP Filter', 'slug' => 'pp-filter', 'parent_id' => $main->id, 'is_active' => true]);

        $this->actingAs(User::factory()->create())
            ->from(route('website-design.homepage-categories.edit'))
            ->patch(route('website-design.homepage-categories.update'), [
                'sections' => [['category_id' => $child->id, 'enabled' => true, 'limit' => 8, 'sort' => 'latest']],
            ])
            ->assertRedirect(route('website-design.homepage-categories.edit'))
            ->assertSessionHasErrors('sections');
    }

    public function test_homepage_uses_saved_order_and_includes_descendant_category_products(): void
    {
        $engineOil = Category::create(['name' => 'Engine Oil', 'slug' => 'engine-oil', 'is_active' => true]);
        $waterFilter = Category::create(['name' => 'Water Filter', 'slug' => 'water-filter', 'is_active' => true]);
        $ppFilter = Category::create(['name' => 'PP Filter', 'slug' => 'pp-filter', 'parent_id' => $waterFilter->id, 'is_active' => true]);
        $oilProduct = Product::create(['title' => 'Synthetic Oil', 'slug' => 'synthetic-oil', 'regular_price' => 900, 'stock_quantity' => 5, 'status' => 'Published', 'visibility' => 'Public']);
        $filterProduct = Product::create(['title' => 'Six Piece Filter', 'slug' => 'six-piece-filter', 'regular_price' => 600, 'stock_quantity' => 5, 'status' => 'Published', 'visibility' => 'Public']);
        $oilProduct->categories()->attach($engineOil);
        $filterProduct->categories()->attach($ppFilter);
        WebsiteSetting::create([
            'id' => 1,
            'homepage_category_sections' => [
                ['category_id' => $waterFilter->id, 'enabled' => true, 'limit' => 8, 'sort' => 'latest'],
                ['category_id' => $engineOil->id, 'enabled' => true, 'limit' => 8, 'sort' => 'latest'],
            ],
        ]);

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/modules/storefront/pages/Home', false)
                ->has('categorySections', 2)
                ->where('categorySections.0.name', 'Water Filter')
                ->where('categorySections.0.products.0.name', 'Six Piece Filter')
                ->where('categorySections.1.name', 'Engine Oil')
                ->where('categorySections.1.products.0.name', 'Synthetic Oil')
                ->has('featuredProducts', 0));
    }

    public function test_empty_or_disabled_category_sections_are_not_rendered(): void
    {
        $empty = Category::create(['name' => 'Empty', 'slug' => 'empty', 'is_active' => true]);
        $disabled = Category::create(['name' => 'Disabled', 'slug' => 'disabled', 'is_active' => true]);
        WebsiteSetting::create([
            'id' => 1,
            'homepage_category_sections' => [
                ['category_id' => $empty->id, 'enabled' => true, 'limit' => 8, 'sort' => 'latest'],
                ['category_id' => $disabled->id, 'enabled' => false, 'limit' => 8, 'sort' => 'latest'],
            ],
        ]);

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('categorySections', 0)->has('featuredProducts', 0));
    }
}
