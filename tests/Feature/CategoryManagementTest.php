<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Inventories\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_category(): void
    {
        Storage::fake('public');

        $response = $this->actingAs(User::factory()->create())->post(route('inventories.categories.store'), [
            'name' => 'Mobile Phones',
            'parent_id' => null,
            'slug' => 'mobile-phones',
            'description' => 'Smartphones and mobile accessories.',
            'image' => UploadedFile::fake()->image('mobile-phones.png', 400, 400),
        ]);

        $response->assertRedirect(route('inventories.categories.index'));
        $response->assertSessionHas('success', 'Category created successfully.');

        $this->assertDatabaseHas('categories', [
            'name' => 'Mobile Phones',
            'slug' => 'mobile-phones',
        ]);

        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        Storage::disk('public')->assertExists($category->image_path);
    }

    public function test_category_can_be_created_under_a_parent(): void
    {
        $parent = Category::query()->create(['name' => 'Electronics', 'slug' => 'electronics']);

        $this->actingAs(User::factory()->create())->post(route('inventories.categories.store'), [
            'name' => 'Laptops',
            'parent_id' => $parent->id,
            'slug' => 'laptops',
            'description' => null,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', ['slug' => 'laptops', 'parent_id' => $parent->id]);
    }

    public function test_categories_are_listed_in_parent_child_order_with_depth(): void
    {
        $parent = Category::query()->create(['name' => 'Electronics', 'slug' => 'electronics']);
        Category::query()->create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $parent->id]);
        Category::query()->create(['name' => 'Apparel', 'slug' => 'apparel']);

        $response = $this->actingAs(User::factory()->create())->get(route('inventories.categories.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('categories.data.0.name', 'Apparel')
            ->where('categories.data.0.products_count', 0)
            ->where('categories.data.0.depth', 0)
            ->where('categories.data.1.name', 'Electronics')
            ->where('categories.data.2.name', 'Phones')
            ->where('categories.data.2.depth', 1));
    }

    public function test_category_slug_must_be_unique(): void
    {
        Category::query()->create(['name' => 'Electronics', 'slug' => 'electronics']);

        $this->actingAs(User::factory()->create())->post(route('inventories.categories.store'), [
            'name' => 'Another Electronics',
            'slug' => 'electronics',
        ])->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_reserved_public_path_cannot_be_used_as_category_slug(): void
    {
        $this->actingAs(User::factory()->create())->post(route('inventories.categories.store'), [
            'name' => 'Checkout',
            'slug' => 'checkout',
        ])->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_active_category_has_a_root_level_public_url(): void
    {
        $category = Category::query()->create(['name' => 'Mobile Phones', 'slug' => 'mobile-phones']);

        $this->get(route('storefront.categories.show', $category->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('category.slug', 'mobile-phones'));
    }

    public function test_svg_upload_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->post(route('inventories.categories.store'), [
            'name' => 'Unsafe Image',
            'slug' => 'unsafe-image',
            'image' => UploadedFile::fake()->createWithContent('unsafe.svg', '<svg onload="alert(1)"></svg>'),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_category_description_html_is_sanitized(): void
    {
        $this->actingAs(User::factory()->create())->post(route('inventories.categories.store'), [
            'name' => 'Safe Content',
            'slug' => 'safe-content',
            'description' => '<h2>Useful details</h2><script>alert(1)</script><p onclick="alert(2)">Safe copy</p>',
        ])->assertSessionHasNoErrors();

        $description = Category::query()->where('slug', 'safe-content')->value('description');

        $this->assertStringContainsString('<h2>Useful details</h2>', $description);
        $this->assertStringContainsString('<p>Safe copy</p>', $description);
        $this->assertStringNotContainsString('<script', $description);
        $this->assertStringNotContainsString('onclick', $description);
    }

    public function test_authenticated_user_can_update_a_category(): void
    {
        $category = Category::query()->create(['name' => 'Old name', 'slug' => 'old-name']);

        $this->actingAs(User::factory()->create())->put(route('inventories.categories.update', $category), [
            'name' => 'New name',
            'slug' => 'new-name',
            'description' => '<p>Updated</p>',
        ])->assertSessionHas('success', 'Category updated successfully.');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New name', 'slug' => 'new-name']);
    }

    public function test_authenticated_user_can_delete_a_category_without_children(): void
    {
        $category = Category::query()->create(['name' => 'Temporary', 'slug' => 'temporary']);

        $this->actingAs(User::factory()->create())
            ->delete(route('inventories.categories.destroy', $category))
            ->assertSessionHas('success', 'Category deleted successfully.');

        $this->assertSoftDeleted($category);
    }
}
