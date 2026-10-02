<?php

namespace Tests\Feature;

use App\Support\Installer\InstallationState;
use App\Support\Installer\InstallerService;
use Mockery;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    public function test_installer_is_not_available_after_the_application_is_installed(): void
    {
        $this->get('/install')->assertNotFound();
    }

    public function test_uninstalled_application_displays_the_guided_installer(): void
    {
        $this->useUninstalledState();

        $this->get('/install')
            ->assertOk()
            ->assertSee('Server requirements')
            ->assertSee('Application & database', false)
            ->assertSee('Administrator');
    }

    public function test_installer_rejects_an_invalid_configuration_before_installing(): void
    {
        $this->useUninstalledState();

        $this->from('/install')
            ->post('/install', [])
            ->assertRedirect('/install')
            ->assertSessionHasErrors([
                'app_name',
                'app_url',
                'timezone',
                'db_host',
                'store_name',
                'admin_email',
                'admin_password',
                'terms',
            ]);
    }

    public function test_database_connection_can_be_checked_without_exposing_credentials(): void
    {
        $this->useUninstalledState();

        $installer = Mockery::mock(InstallerService::class);
        $installer->shouldReceive('testDatabase')
            ->once()
            ->with(Mockery::on(fn (array $data): bool => $data['db_database'] === 'ittiba_store'))
            ->andReturn(['tables' => 0]);
        $this->app->instance(InstallerService::class, $installer);

        $this->postJson('/install/test-database', [
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_database' => 'ittiba_store',
            'db_username' => 'installer',
            'db_password' => 'secret',
        ])->assertOk()
            ->assertJson([
                'tables' => 0,
                'message' => 'Connection successful. The database is empty and ready.',
            ])
            ->assertJsonMissing(['db_password' => 'secret']);
    }

    private function useUninstalledState(): void
    {
        $state = Mockery::mock(InstallationState::class);
        $state->shouldReceive('isInstalled')->andReturnFalse();
        $this->app->instance(InstallationState::class, $state);
    }
}
