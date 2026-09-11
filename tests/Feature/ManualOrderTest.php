<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class ManualOrderTest extends TestCase {
 use RefreshDatabase;
 public function test_admin_can_edit_and_delete_a_safe_order_with_stock_restoration(): void {
  $admin=User::factory()->create(['is_admin'=>true]);
  $productId=DB::table('products')->insertGetId(['title'=>'Editable Product','slug'=>'editable-product','regular_price'=>500,'sku'=>'EDIT-1','stock_quantity'=>8,'status'=>'Published','visibility'=>'Public','created_at'=>now(),'updated_at'=>now()]);
  $orderId=DB::table('orders')->insertGetId(['order_number'=>'#EDIT01','source'=>'phone','customer_name'=>'Edit Customer','phone'=>'01700000000','address'=>'Dhaka','city'=>'Dhaka','delivery_zone'=>'inside_dhaka','payment_method'=>'cod','payment_status'=>'pending','status'=>'pending','subtotal'=>1000,'discount_total'=>0,'shipping_total'=>0,'total'=>1000,'shipping_label_generated_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
  DB::table('order_items')->insert(['order_id'=>$orderId,'product_id'=>$productId,'product_title'=>'Editable Product','sku'=>'EDIT-1','unit_price'=>500,'quantity'=>2,'line_total'=>1000,'stock_shortage_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($admin)->get(route('orders.edit',$orderId))->assertOk()->assertInertia(fn($page)=>$page->component('app/modules/orders/pages/Create',false)->where('editingOrder.id',$orderId)->where('editingOrder.items.0.quantity',2));
  $payload=['source'=>'phone','customer_name'=>'Updated Buyer','phone'=>'01700000000','email'=>null,'address'=>'Updated address','city'=>'Dhaka','delivery_zone'=>'inside_dhaka','note'=>null,'payment_method'=>'cod','payment_status'=>'pending','discount'=>50,'items'=>[['product_id'=>$productId,'variant_id'=>null,'quantity'=>1,'unit_price'=>500]]];
  $this->actingAs($admin)->patch(route('orders.update',$orderId),$payload)->assertRedirect(route('orders.show',$orderId));
  $this->assertDatabaseHas('orders',['id'=>$orderId,'customer_name'=>'Updated Buyer','subtotal'=>500,'discount_total'=>50,'shipping_label_generated_at'=>null]);
  $this->assertDatabaseHas('products',['id'=>$productId,'stock_quantity'=>9]);
  $this->actingAs($admin)->delete(route('orders.destroy',$orderId))->assertRedirect(route('orders.index'));
  $this->assertDatabaseMissing('orders',['id'=>$orderId]);
  $this->assertDatabaseHas('products',['id'=>$productId,'stock_quantity'=>10]);
 }
 public function test_admin_can_quick_create_a_customer_with_an_address(): void {
  $admin=User::factory()->create(['is_admin'=>true]);
  $response=$this->actingAs($admin)->postJson(route('orders.customers.store'),['name'=>'Quick Customer','phone'=>'01712345678','email'=>'quick@example.com','city'=>'Dhaka','address_label'=>'Office','address'=>'Motijheel, Dhaka']);
  $response->assertCreated()->assertJsonPath('customer.name','Quick Customer')->assertJsonPath('customer.addressLabel','Office');
  $customer=User::query()->where('phone','01712345678')->firstOrFail();
  $this->assertFalse($customer->is_admin);
  $this->assertDatabaseHas('customer_addresses',['user_id'=>$customer->id,'label'=>'Office','delivery_zone'=>'inside_dhaka','address'=>'Motijheel, Dhaka']);
  $this->actingAs($admin)->patchJson(route('orders.customers.update',$customer),['name'=>'Updated Customer','phone'=>'01712345678','email'=>'updated@example.com','city'=>'Chattogram','address_label'=>'Home','address'=>'Agrabad'])->assertOk()->assertJsonPath('customer.name','Updated Customer');
  $this->assertDatabaseHas('users',['id'=>$customer->id,'name'=>'Updated Customer','email'=>'updated@example.com']);
  $this->assertDatabaseHas('customer_addresses',['user_id'=>$customer->id,'label'=>'Home','delivery_zone'=>'outside_dhaka','address'=>'Agrabad']);
 }
 public function test_admin_can_create_a_phone_order_with_products_and_discount(): void {
  $admin=User::factory()->create(['is_admin'=>true]);
  $productId=DB::table('products')->insertGetId(['title'=>'Manual Product','slug'=>'manual-product','regular_price'=>500,'sku'=>'MAN-1','stock_quantity'=>10,'status'=>'Published','visibility'=>'Public','created_at'=>now(),'updated_at'=>now()]);
  \App\Modules\Settings\Models\WebsiteSetting::firstOrCreate(['id'=>1])->update(['delivery_enabled'=>true,'delivery_inside_dhaka'=>80,'delivery_outside_dhaka'=>150]);
  $this->actingAs($admin)->get(route('orders.create'))->assertOk()->assertInertia(fn($page)=>$page->component('app/modules/orders/pages/Create',false)->where('products.0.id',$productId)->where('deliveryCharges.insideDhaka',80)->where('deliveryCharges.outsideDhaka',150));
  $response=$this->actingAs($admin)->post(route('orders.store'),['source'=>'phone','customer_name'=>'Phone Customer','phone'=>'01700000000','email'=>'buyer@example.com','address'=>'Dhaka address','city'=>'Dhaka','delivery_zone'=>'inside_dhaka','note'=>'Call first','payment_method'=>'cod','payment_status'=>'pending','discount'=>50,'items'=>[['product_id'=>$productId,'variant_id'=>null,'quantity'=>2,'unit_price'=>450]]]);
  $orderId=DB::table('orders')->value('id');
  $response->assertRedirect(route('orders.show',$orderId));
  $this->assertDatabaseHas('orders',['id'=>$orderId,'source'=>'phone','subtotal'=>900,'discount_total'=>50,'payment_status'=>'pending']);
  $this->assertDatabaseHas('order_items',['order_id'=>$orderId,'product_id'=>$productId,'quantity'=>2,'line_total'=>900]);
  $this->assertDatabaseHas('products',['id'=>$productId,'stock_quantity'=>8]);
 }
}
