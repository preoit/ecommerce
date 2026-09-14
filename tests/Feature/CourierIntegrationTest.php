<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http,Queue};
use App\Models\User;
use App\Modules\Courier\Models\{Courier,CourierOrder};
use App\Modules\Courier\Services\BookingService;
class CourierIntegrationTest extends TestCase {
    use RefreshDatabase;
    private function courier(): Courier { $c=Courier::where('slug','steadfast')->firstOrFail();$c->update(['active'=>true,'credentials'=>['api_key'=>'private-key','secret_key'=>'private-secret']]);return $c; }
    private function order(): int {
        $id=DB::table('orders')->insertGetId(['order_number'=>'T'.uniqid(),'customer_name'=>'Test Customer','phone'=>'01700000000','address'=>'House 10, Road 2, Dhaka','city'=>'Dhaka','status'=>'confirmed','payment_status'=>'pending','payment_method'=>'cod','subtotal'=>500,'shipping_total'=>80,'total'=>580,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('order_items')->insert(['order_id'=>$id,'product_title'=>'Test','quantity'=>1,'unit_price'=>500,'line_total'=>500,'created_at'=>now(),'updated_at'=>now()]); return $id;
    }
    public function test_settings_protect_secrets_and_reject_untrusted_urls(): void {
        $c=$this->courier();$this->assertStringNotContainsString('private-secret',DB::table('couriers')->value('credentials'));
        $this->actingAs(User::factory()->create())->get(route('couriers.settings'))->assertOk()->assertDontSee('private-secret')->assertDontSee('private-key');
        $this->patchJson(route('couriers.save',$c),['active'=>true,'sandbox_mode'=>false,'api_url'=>'https://attacker.test'])->assertUnprocessable();
        $this->actingAs(User::factory()->create(['is_admin'=>false]))->get(route('couriers.settings'))->assertRedirect();
    }
    public function test_booking_is_queued_and_duplicates_cannot_be_submitted(): void {
        Queue::fake();$c=$this->courier();$id=$this->order();$this->actingAs(User::factory()->create());
        $data=['courier_id'=>$c->id,'orders'=>[['id'=>$id]]];
        $this->postJson(route('couriers.book'),$data)->assertOk()->assertJsonPath('results.0.success',true);
        $this->postJson(route('couriers.book'),$data)->assertOk()->assertJsonPath('results.0.success',false);
        $this->assertDatabaseCount('courier_orders',1);Queue::assertPushed(\App\Modules\Courier\Jobs\SubmitParcel::class,1);
    }
    public function test_provider_booking_status_mapping_and_payment_are_separate(): void {
        $c=$this->courier();$c->update(['status_mapping'=>['delivered'=>'completed']]);$id=$this->order();$s=new BookingService;
        Http::fake(['*/create_order'=>Http::response(['status'=>200,'consignment'=>['consignment_id'=>123,'invoice'=>'invoice','tracking_code'=>'TRACK']]),'*/status_by_cid/*'=>Http::response(['status'=>200,'delivery_status'=>'delivered'])]);
        $b=$s->reserve($id,$c,[]);$s->submit($b);$b->refresh();$this->assertSame('pending',$b->status);$s->sync($b);
        $this->assertDatabaseHas('orders',['id'=>$id,'status'=>'completed','payment_status'=>'pending']);$this->assertNull($b->fresh()->collected_cod);
        $s->sync($b->fresh());$this->assertDatabaseCount('courier_status_logs',2);
    }
    public function test_uncertain_response_keeps_duplicate_lock(): void {
        Http::fake(['*'=>Http::response([],500)]);$s=new BookingService;$id=$this->order();$b=$s->reserve($id,$this->courier(),[]);$s->submit($b);
        $this->assertSame('needs_verification',$b->fresh()->status);$this->assertSame($id,$b->fresh()->active_order_id);
    }
    public function test_pathao_auth_and_booking_payload(): void {
        $c=Courier::where('slug','pathao')->first();$c->update(['active'=>true,'credentials'=>['client_id'=>'client','client_secret'=>'secret','store_id'=>'1']]);
        Http::fake(['*/external/login'=>Http::response(['access_token'=>'token','expires_in'=>3600]),'*/orders'=>Http::response(['data'=>['consignment_id'=>'P123','merchant_order_id'=>'test','delivery_fee'=>80]],201)]);
        $s=new BookingService;$b=$s->reserve($this->order(),$c,['city_id'=>1,'zone_id'=>2,'area_id'=>3]);$s->submit($b);
        $this->assertSame('P123',$b->fresh()->consignment_id);Http::assertSent(fn($r)=>str_ends_with($r->url(),'/orders')&&$r['recipient_city']===1&&(float)$r['amount_to_collect']===580.0);
    }
    public function test_inactive_courier_and_sandbox_mapping_are_safe(): void {
        $c=$this->courier();$c->update(['active'=>false]);$this->actingAs(User::factory()->create());
        $this->getJson(route('couriers.order',$id=$this->order()))->assertOk()->assertJsonCount(0,'couriers');
        $this->postJson(route('couriers.book'),['courier_id'=>$c->id,'orders'=>[['id'=>$id]]])->assertJsonPath('results.0.success',false);
        $this->assertDatabaseCount('courier_orders',0);
        $c->update(['active'=>true]);$b=(new BookingService)->reserve($id,$c,[]);
        $this->postJson(route('couriers.settlement',$b),['amount'=>580,'reference'=>'TEST'])->assertUnprocessable();
    }
    public function test_unknown_status_does_not_complete_order_and_settlement_is_audited(): void {
        $c=$this->courier();$id=$this->order();$s=new BookingService;
        Http::fake(['*/create_order'=>Http::response(['status'=>200,'consignment'=>['consignment_id'=>987,'tracking_code'=>'TEST']]),'*/status_by_cid/*'=>Http::response(['status'=>200,'delivery_status'=>'new_provider_status'])]);
        $b=$s->reserve($id,$c,[]);$s->submit($b);$s->sync($b->fresh());
        $this->assertDatabaseHas('orders',['id'=>$id,'status'=>'confirmed']);
        $this->actingAs(User::factory()->create())->postJson(route('couriers.settlement',$b),['amount'=>500,'reference'=>'PAYMENT-001'])->assertOk();
        $this->assertSame('500.00',number_format((float)$b->fresh()->collected_cod,2,'.',''));
        $this->assertDatabaseHas('courier_status_logs',['courier_order_id'=>$b->id,'api_status'=>'manual_settlement']);
        $this->postJson(route('couriers.settlement',$b),['amount'=>999,'reference'=>'PAYMENT-002'])->assertUnprocessable();
    }
}
