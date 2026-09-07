<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Inventories\Units\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_create_and_edit_unit_pages(): void
    {
        $user = User::factory()->create();
        $unit = Unit::query()->create(['name' => 'Piece', 'slug' => 'piece']);

        $this->actingAs($user)->get(route('inventories.units.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/modules/inventories/categories/pages/Form', false)
                ->where('resource', 'units')
                ->where('entityLabel', 'unit')
                ->where('category', null));

        $this->actingAs($user)->get(route('inventories.units.edit', $unit))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/modules/inventories/categories/pages/Form', false)
                ->where('resource', 'units')
                ->where('entityLabel', 'unit')
                ->where('category.id', $unit->id));
    }

    public function test_authenticated_user_can_create_and_update_a_unit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('inventories.units.store'), [
            'name' => 'Kilogram',
            'slug' => 'kilogram',
        ])->assertRedirect(route('inventories.units.index'))
            ->assertSessionHas('success', 'Unit created successfully.');

        $unit = Unit::query()->where('slug', 'kilogram')->firstOrFail();

        $this->actingAs($user)->put(route('inventories.units.update', $unit), [
            'name' => 'KG',
            'slug' => 'kg',
        ])->assertRedirect(route('inventories.units.index'))
            ->assertSessionHas('success', 'Unit updated successfully.');

        $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'KG', 'slug' => 'kg']);
    }
}