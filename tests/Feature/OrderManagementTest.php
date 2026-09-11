<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_list_exposes_unviewed_state_and_details_marks_order_viewed(): void
    {
        $user = User::factory()->create();
        $orderId = DB::table('orders')->insertGetId([
            'order_number' => '#ORD01',
            'customer_name' => 'Test Customer',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'city' => 'Dhaka',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'subtotal' => 500,
            'shipping_total' => 0,
            'total' => 500,
            'has_stock_shortage' => false,
            'viewed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_title' => 'Test Product',
            'unit_price' => 500,
            'quantity' => 1,
            'line_total' => 500,
            'stock_shortage_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get(route('orders.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/modules/orders/pages/Index', false)
                ->where('orders.0.id', $orderId)
                ->where('orders.0.viewed', false)
                ->where('orders.0.itemCount', 1)
                ->where('orders.0.products.0.name', 'Test Product')
                ->where('orders.0.products.0.quantity', 1)
                ->where('orders.0.products.0.slug', null));

        $this->actingAs($user)->get(route('orders.show', $orderId))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/modules/orders/pages/Show', false)
                ->where('order.id', $orderId)
                ->where('order.items.0.title', 'Test Product'));

        $this->assertDatabaseMissing('orders', ['id' => $orderId, 'viewed_at' => null]);
        $this->actingAs($user)->post(route('orders.shipping-label.generate', $orderId))->assertStatus(422);
        $this->actingAs($user)->patch(route('orders.status.update', $orderId), ['status' => 'confirmed'])->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'confirmed']);
        $this->actingAs($user)->post(route('orders.shipping-label.generate', $orderId))
            ->assertRedirect(route('orders.shipping-label.show', $orderId));
        $this->assertDatabaseMissing('orders', ['id' => $orderId, 'shipping_label_generated_at' => null]);
        $this->actingAs($user)->get(route('orders.shipping-label.show', $orderId))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/modules/orders/pages/ShippingLabel', false)
                ->where('order.id', $orderId)
                ->where('order.items.0.title', 'Test Product'));
        $this->actingAs($user)->get(route('orders.index'))
            ->assertInertia(fn ($page) => $page->where('orders.0.viewed', true)->where('orders.0.status', 'Confirmed'));
    }
}
