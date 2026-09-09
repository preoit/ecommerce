<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Schema;
use App\Modules\Settings\Models\CommunicationSetting;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        if (Schema::hasTable('communication_settings') && ($settings = CommunicationSetting::find(1))) {
            if ($settings->email_enabled) config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => $settings->mail_host, 'mail.mailers.smtp.port' => $settings->mail_port, 'mail.mailers.smtp.username' => $settings->mail_username, 'mail.mailers.smtp.password' => $settings->mail_password, 'mail.mailers.smtp.encryption' => $settings->mail_encryption === 'none' ? null : $settings->mail_encryption, 'mail.from.address' => $settings->mail_from_address, 'mail.from.name' => $settings->mail_from_name]);
            if ($settings->sms_enabled) config(['services.mram.api_key' => $settings->mram_api_key, 'services.mram.sender_id' => $settings->mram_sender_id]);
        }
    }
}
