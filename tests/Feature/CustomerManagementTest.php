<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_registered_customers_with_real_account_summary(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create([
            'is_admin' => false,
            'name' => 'Registered Buyer',
            'phone' => '01712345678',
            'phone_verified_at' => now(),
        ]);
        $customer->addresses()->create([
            'label' => 'Home', 'recipient_name' => $customer->name, 'phone' => $customer->phone,
            'delivery_zone' => 'inside_dhaka', 'district' => 'Dhaka', 'city' => 'Dhaka',
            'address' => 'Road 1', 'is_default' => true,
        ]);
        DB::table('orders')->insert([
            'order_number' => '#CUSTOMER1', 'user_id' => $customer->id, 'customer_name' => $customer->name,
            'phone' => $customer->phone, 'address' => 'Road 1', 'city' => 'Dhaka',
            'payment_method' => 'cod', 'payment_status' => 'paid', 'status' => 'completed',
            'subtotal' => 750, 'shipping_total' => 50, 'total' => 800,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('customers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/modules/customers/pages/Index', false)
                ->where('stats.registered', 1)
                ->where('stats.withOrders', 1)
                ->where('stats.totalSpent', 800)
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'Registered Buyer')
                ->where('customers.data.0.orders', 1)
                ->where('customers.data.0.completedOrders', 1)
                ->where('customers.data.0.addresses', 1)
                ->where('customers.data.0.spent', 800));
    }

    public function test_customer_search_does_not_return_admin_accounts(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Person']);
        User::factory()->create(['is_admin' => false, 'name' => 'Buyer Person']);

        $this->actingAs($admin)->get(route('customers.index', ['search' => 'Person']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'Buyer Person')
                ->where('filters.search', 'Person'));
    }
    public function test_admin_can_view_a_customer_activity_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['is_admin' => false]);
        $customer->addresses()->create([
            'label' => 'Office', 'recipient_name' => $customer->name, 'phone' => '01712345678',
            'delivery_zone' => 'inside_dhaka', 'district' => 'Dhaka', 'city' => 'Dhaka',
            'address' => 'Road 2', 'is_default' => true,
        ]);

        $this->actingAs($admin)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/modules/customers/pages/Show', false)
                ->where('customer.id', $customer->id)
                ->where('stats.addresses', 1)
                ->where('stats.orders', 0)
                ->has('addresses', 1));
    }

    public function test_editing_contact_details_resets_their_verification(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
            'phone' => '01712345678',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('customers.update', $customer), [
            'name' => 'Updated Customer',
            'email' => 'updated@example.com',
            'phone' => '01812345678',
        ])->assertRedirect();

        $customer->refresh();
        $this->assertSame('Updated Customer', $customer->name);
        $this->assertSame('updated@example.com', $customer->email);
        $this->assertNull($customer->email_verified_at);
        $this->assertNull($customer->phone_verified_at);
    }

    public function test_only_unverified_customers_without_orders_can_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $deletable = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        $this->actingAs($admin)->delete(route('customers.destroy', $deletable))
            ->assertRedirect(route('customers.index'));
        $this->assertDatabaseMissing('users', ['id' => $deletable->id]);

        $verified = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
            'phone_verified_at' => null,
        ]);

        $this->actingAs($admin)->from(route('customers.index'))
            ->delete(route('customers.destroy', $verified))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasErrors('customer');
        $this->assertDatabaseHas('users', ['id' => $verified->id]);
    }
}
