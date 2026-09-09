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
}