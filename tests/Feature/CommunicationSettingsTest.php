<?php
namespace Tests\Feature;
use App\Models\User;use App\Modules\Settings\Models\CommunicationSetting;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;
class CommunicationSettingsTest extends TestCase
{
 use RefreshDatabase;
 public function test_admin_can_save_encrypted_email_and_sms_settings():void
 { $admin=User::factory()->create(['is_admin'=>true]);$payload=['email_enabled'=>true,'mail_host'=>'smtp.example.com','mail_port'=>587,'mail_username'=>'mailer','mail_password'=>'mail-secret','mail_encryption'=>'tls','mail_from_address'=>'shop@example.com','mail_from_name'=>'Shop','sms_enabled'=>true,'mram_api_key'=>'sms-secret','mram_sender_id'=>'SHOP'];$this->actingAs($admin)->patch(route('settings.communication.update'),$payload)->assertSessionHasNoErrors();$setting=CommunicationSetting::firstOrFail();$this->assertSame('mail-secret',$setting->mail_password);$this->assertSame('sms-secret',$setting->mram_api_key);$raw=\Illuminate\Support\Facades\DB::table('communication_settings')->first();$this->assertNotSame('mail-secret',$raw->mail_password);$this->assertNotSame('sms-secret',$raw->mram_api_key);$this->actingAs($admin)->get(route('settings.communication'))->assertInertia(fn($page)=>$page->component('app/modules/settings/pages/Communication', false)->where('settings.hasMailPassword',true)->where('settings.hasMramApiKey',true)->missing('settings.mailPassword')->missing('settings.mramApiKey')); }
 public function test_customer_cannot_open_communication_settings():void
 { $customer=User::factory()->create(['is_admin'=>false]);$this->actingAs($customer)->get(route('settings.communication'))->assertRedirect(route('account.dashboard')); }
}