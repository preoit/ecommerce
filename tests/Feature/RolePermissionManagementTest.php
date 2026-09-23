<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_roles_and_admin_users(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('roles.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin-users.index'))->assertOk();
    }

    public function test_restricted_admin_can_only_open_granted_features(): void
    {
        $role = Role::create(['name' => 'Product Viewer', 'slug' => 'product-viewer']);
        $role->permissions()->attach(Permission::where('name', 'products.view')->value('id'));
        $admin = User::factory()->create();
        $admin->roles()->sync([$role->id]);

        $this->actingAs($admin)->get(route('inventories.products.index'))->assertOk();
        $this->actingAs($admin)->get(route('inventories.products.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin-users.index'))->assertForbidden();
    }

    public function test_role_can_be_created_with_selected_permissions(): void
    {
        $admin = User::factory()->create();
        $permission = Permission::where('name', 'orders.view')->firstOrFail();

        $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Order Viewer',
            'description' => 'Can inspect orders.',
            'permissions' => [$permission->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('roles', ['slug' => 'order-viewer']);
        $this->assertDatabaseHas('permission_role', ['permission_id' => $permission->id]);
    }
}
