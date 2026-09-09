<?php
namespace Tests\Feature;
use App\Models\User;
use App\Modules\Customers\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;
    public function test_customer_can_manage_only_their_delivery_addresses(): void
    {
        $customer=User::factory()->create(['is_admin'=>false]);
        $other=User::factory()->create(['is_admin'=>false]);
        $payload=['label'=>'Home','recipient_name'=>'Customer','phone'=>'01700000000','delivery_zone'=>'inside_dhaka','district'=>'Dhaka','city'=>'Dhaka','area'=>'Dhanmondi','address'=>'Road 1','postal_code'=>'1205','landmark'=>null,'is_default'=>true];
        $this->actingAs($customer)->post(route('account.addresses.store'),$payload)->assertRedirect();
        $address=$customer->addresses()->first();
        $this->assertTrue($address->is_default);
        $this->actingAs($other)->patch(route('account.addresses.update',$address),$payload)->assertNotFound();
        $this->actingAs($other)->delete(route('account.addresses.destroy',$address))->assertNotFound();
    }
    public function test_customer_is_redirected_away_from_admin_dashboard(): void
    {
        $customer=User::factory()->create(['is_admin'=>false]);
        $this->actingAs($customer)->get(route('dashboard'))->assertRedirect(route('account.dashboard'));
        $this->actingAs($customer)->get(route('account.dashboard'))->assertOk();
    }
}