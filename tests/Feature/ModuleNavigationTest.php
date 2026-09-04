<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ModuleNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_home_uses_the_storefront_module(): void
    {
        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/modules/storefront/pages/Home', false)
            );
    }

    public function test_guests_are_redirected_from_admin_modules(): void
    {
        $this->get(route('inventories.products.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_open_every_admin_module(): void
    {
        $this->actingAs(User::factory()->create());

        $routes = [
            'dashboard',
            'inventories.products.index',
            'inventories.categories.index',
            'inventories.brands.index',
            'inventories.units.index',
            'orders.index',
            'customers.index',
            'coupons.index',
            'reviews.index',
            'reports.index',
            'settings.index',
        ];

        foreach ($routes as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_category_module_returns_its_dedicated_page_data(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('inventories.categories.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/modules/inventories/categories/pages/Index', false)
                ->has('categories.data')
                ->has('parentOptions')
            );
    }
}
