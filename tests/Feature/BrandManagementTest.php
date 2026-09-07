<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Inventories\Brands\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_and_update_a_brand(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('inventories.brands.store'), [
            'name' => 'Aqua Pro',
            'slug' => 'aqua-pro',
            'short_description' => '<p><strong>Safe summary</strong></p><script>alert(1)</script>',
            'description' => '<p onclick="alert(2)">Safe details</p>',
        ])->assertRedirect(route('inventories.brands.index'))
            ->assertSessionHas('success', 'Brand created successfully.');

        $brand = Brand::query()->where('slug', 'aqua-pro')->firstOrFail();
        $this->assertStringNotContainsString('<script', $brand->short_description);
        $this->assertStringNotContainsString('onclick', $brand->description);

        $this->actingAs($user)->put(route('inventories.brands.update', $brand), [
            'name' => 'Aqua Pro Updated',
            'slug' => 'aqua-pro-updated',
            'short_description' => '<p>Updated summary</p>',
            'description' => '<p>Updated details</p>',
        ])->assertRedirect(route('inventories.brands.index'))
            ->assertSessionHas('success', 'Brand updated successfully.');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Aqua Pro Updated',
            'slug' => 'aqua-pro-updated',
        ]);
    }
}