<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class ManualOrderTest extends TestCase {
 use RefreshDatabase;
 public function test_admin_can_create_a_phone_order_with_products_and_discount(): void {
  $admin=User::factory()->create(['is_admin'=>true]);
  $productId=DB::table('products')->insertGetId(['title'=>'Manual Product','slug'=>'manual-product','regular_price'=>500,'sku'=>'MAN-1','stock_quantity'=>10,'status'=>'Published','visibility'=>'Public','created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($admin)->get(route('orders.create'))->assertOk()->assertInertia(fn($page)=>$page->component('app/modules/orders/pages/Create',false)->where('products.0.id',$productId));
  $response=$this->actingAs($admin)->post(route('orders.store'),['source'=>'phone','customer_name'=>'Phone Customer','phone'=>'01700000000','email'=>'buyer@example.com','address'=>'Dhaka address','city'=>'Dhaka','delivery_zone'=>'inside_dhaka','note'=>'Call first','payment_method'=>'cod','payment_status'=>'pending','discount'=>50,'items'=>[['product_id'=>$productId,'variant_id'=>null,'quantity'=>2,'unit_price'=>450]]]);
  $orderId=DB::table('orders')->value('id');
  $response->assertRedirect(route('orders.show',$orderId));
  $this->assertDatabaseHas('orders',['id'=>$orderId,'source'=>'phone','subtotal'=>900,'discount_total'=>50,'payment_status'=>'pending']);
  $this->assertDatabaseHas('order_items',['order_id'=>$orderId,'product_id'=>$productId,'quantity'=>2,'line_total'=>900]);
  $this->assertDatabaseHas('products',['id'=>$productId,'stock_quantity'=>8]);
 }
}
