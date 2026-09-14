<?php

namespace Tests\Feature;

use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutPhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_optionally_verify_phone_with_mram_before_ordering(): void
    {
        config(['services.mram.api_key' => 'test-key']);
        $otp = null;
        Http::fake(function (Request $request) use (&$otp) {
            preg_match('/\b(\d{4})\b/', (string) $request['msg'], $matches);
            $otp = $matches[1] ?? null;
            return Http::response('SMS SUBMITTED', 200);
        });

        $this->postJson(route('storefront.checkout.phone-verification.send'), ['phone' => '01700-000000'])
            ->assertOk()->assertJsonPath('sent', true);
        $this->assertNotNull($otp);
        $this->postJson(route('storefront.checkout.phone-verification.verify'), ['phone' => '01700000000', 'code' => $otp])
            ->assertOk()->assertJsonPath('verified', true);

        $this->addProductToCart();
        $this->post(route('storefront.checkout.place-order'), $this->orderData())->assertRedirect();
        $this->assertDatabaseHas('orders', ['phone' => '01700000000']);
        $this->assertNotNull(\DB::table('orders')->value('phone_verified_at'));
    }

    public function test_phone_verification_is_not_required_to_place_an_order(): void
    {
        $this->addProductToCart();
        $this->get(route('storefront.checkout'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/modules/storefront/checkout/pages/Index', false)
                ->where('phoneVerification.verified', false));
        $this->post(route('storefront.checkout.place-order'), $this->orderData())->assertRedirect();
        $this->assertNull(\DB::table('orders')->value('phone_verified_at'));
    }

    private function addProductToCart(): void
    {
        WebsiteSetting::firstOrCreate(['id' => 1], ['allow_out_of_stock_orders' => false]);
        $product = Product::create(['title' => 'OTP Product', 'slug' => 'otp-product', 'regular_price' => 100, 'stock_quantity' => 5, 'status' => 'Published', 'visibility' => 'Public']);
        $this->postJson(route('storefront.products.cart', $product), ['quantity' => 1])->assertOk();
    }

    private function orderData(): array
    {
        return ['customer_name' => 'Customer', 'phone' => '01700000000', 'address' => 'Dhaka', 'city' => 'Dhanmondi', 'district' => 'Dhaka', 'delivery_zone' => 'inside_dhaka', 'payment_method' => 'cod'];
    }
}
