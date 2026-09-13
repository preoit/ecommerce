<?php
namespace Tests\Feature;

use App\Models\User;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderContactSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_normalized_order_numbers_and_share_them_with_storefront(): void
    {
        $this->actingAs(User::factory()->create())->patch(route('settings.website.update'), [
            'website_name' => 'Shop', 'order_whatsapp' => '01712345678', 'order_phone' => '+880 18-12345678',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('website_settings', ['id' => 1, 'order_whatsapp' => '8801712345678', 'order_phone' => '8801812345678']);
        $this->get('/')->assertInertia(fn ($page) => $page->where('website.orderWhatsapp', '8801712345678')->where('website.orderPhone', '8801812345678'));
        $this->patch(route('settings.website.update'), ['website_name' => 'Shop', 'order_whatsapp' => '', 'order_phone' => ''])->assertSessionHasNoErrors();
        $this->assertNull(WebsiteSetting::find(1)->order_whatsapp);
        $this->assertNull(WebsiteSetting::find(1)->order_phone);
    }

    public function test_invalid_contact_numbers_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())->patch(route('settings.website.update'), ['website_name' => 'Shop', 'order_phone' => 'not-a-number'])->assertSessionHasErrors('order_phone');
    }
}
