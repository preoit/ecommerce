<?php

namespace App\Providers;

use App\Modules\Settings\Models\CommunicationSetting;
use App\Support\Installer\InstallationState;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(InstallationState::class);

        if (! $this->app->environment('testing')
            && ! is_file((string) config('installer.lock_file', storage_path('app/private/installed.json')))) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
                'queue.default' => 'sync',
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        try {
            if (! $this->app->make(InstallationState::class)->isInstalled()) {
                return;
            }

            if (Schema::hasTable('communication_settings') && ($settings = CommunicationSetting::find(1))) {
                if ($settings->email_enabled) {
                    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => $settings->mail_host, 'mail.mailers.smtp.port' => $settings->mail_port, 'mail.mailers.smtp.username' => $settings->mail_username, 'mail.mailers.smtp.password' => $settings->mail_password, 'mail.mailers.smtp.encryption' => $settings->mail_encryption === 'none' ? null : $settings->mail_encryption, 'mail.from.address' => $settings->mail_from_address, 'mail.from.name' => $settings->mail_from_name]);
                }
                if ($settings->sms_enabled) {
                    config(['services.mram.api_key' => $settings->mram_api_key, 'services.mram.sender_id' => $settings->mram_sender_id]);
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
