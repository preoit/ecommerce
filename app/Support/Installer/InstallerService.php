<?php

namespace App\Support\Installer;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Modules\Settings\Models\WebsiteSetting;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PDO;
use RuntimeException;
use Throwable;

class InstallerService
{
    public function __construct(private readonly InstallationState $state) {}

    /** @param array<string, mixed> $data
     * @return array{tables: int}
     */
    public function testDatabase(array $data): array
    {
        $this->configureConnection('installer', $data);

        try {
            DB::purge('installer');
            $connection = DB::connection('installer');
            $connection->getPdo();
            $connection->select('SELECT 1');
            $tables = $connection->getSchemaBuilder()->getTableListing();

            return ['tables' => count($tables)];
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'db_connection' => 'Database connection failed. Check the host, port, database name, username, and password.',
            ]);
        } finally {
            DB::disconnect('installer');
        }
    }

    /** @param array<string, mixed> $data
     * @return array{warnings: array<int, string>}
     */
    public function install(array $data): array
    {
        $databaseCheck = $this->testDatabase($data);
        $resume = $this->state->canResume($data);

        if ($databaseCheck['tables'] > 0 && ! $resume) {
            throw ValidationException::withMessages([
                'db_database' => 'The selected database is not empty. For safety, choose a new empty database; existing tables will never be overwritten.',
            ]);
        }

        if (! $resume) {
            $this->state->begin($data);
        }

        $environmentPath = base_path('.env');
        $originalEnvironment = File::isFile($environmentPath) ? File::get($environmentPath) : null;
        $warnings = [];

        try {
            $appKey = filled(config('app.key')) ? (string) config('app.key') : 'base64:'.base64_encode(random_bytes(32));
            $this->writeEnvironment($data, $appKey);
            $this->configureRuntime($data, $appKey);

            Artisan::call('optimize:clear');
            $this->runCommand('migrate', ['--force' => true], 'Database migration failed.');
            $this->createAdministrator($data);
            $this->createWebsiteSettings($data);

            if (! file_exists(public_path('storage'))) {
                try {
                    $this->runCommand('storage:link', [], 'The public storage link could not be created.');
                } catch (Throwable) {
                    $warnings[] = 'Create the public storage link manually with: php artisan storage:link';
                }
            }

            $this->runCommand('view:cache', [], 'View cache could not be generated.');
            $this->runCommand('config:cache', [], 'Configuration cache could not be generated.');
            $this->state->markInstalled();

            return ['warnings' => $warnings];
        } catch (Throwable $exception) {
            $this->restoreEnvironment($environmentPath, $originalEnvironment);
            try {
                Artisan::call('config:clear');
            } catch (Throwable) {
                // The original environment is already restored.
            }
            report($exception);

            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'installation' => 'Installation could not be completed. No existing database was overwritten. Correct the server issue and try again.',
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function configureRuntime(array $data, string $appKey): void
    {
        $mysql = $this->connectionConfiguration($data);
        config([
            'app.name' => $data['app_name'],
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => $data['app_url'],
            'app.timezone' => $data['timezone'],
            'app.key' => $appKey,
            'installer.key_is_temporary' => false,
            'database.default' => 'mysql',
            'database.connections.mysql' => $mysql,
            'queue.default' => 'sync',
        ]);
        date_default_timezone_set((string) $data['timezone']);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
    }

    /** @param array<string, mixed> $data */
    private function configureConnection(string $name, array $data): void
    {
        config(["database.connections.{$name}" => $this->connectionConfiguration($data)]);
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function connectionConfiguration(array $data): array
    {
        return [
            'driver' => 'mysql',
            'host' => $data['db_host'],
            'port' => (int) $data['db_port'],
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => $data['db_password'] ?? '',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? [PDO::ATTR_TIMEOUT => 5] : [],
        ];
    }

    /** @param array<string, mixed> $data */
    private function writeEnvironment(array $data, string $appKey): void
    {
        $path = base_path('.env');
        $content = File::isFile($path) ? File::get($path) : File::get(base_path('.env.example'));
        $values = [
            'APP_NAME' => $data['app_name'],
            'APP_ENV' => 'production',
            'APP_KEY' => $appKey,
            'APP_DEBUG' => false,
            'APP_URL' => $data['app_url'],
            'APP_TIMEZONE' => $data['timezone'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (int) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'QUEUE_CONNECTION' => 'sync',
        ];

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->environmentValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';
            $content = preg_match($pattern, $content)
                ? (string) preg_replace($pattern, $line, $content)
                : rtrim($content).PHP_EOL.$line.PHP_EOL;
        }

        if (File::put($path, rtrim($content).PHP_EOL, true) === false) {
            throw new RuntimeException('The .env file could not be written.');
        }
    }

    private function environmentValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], (string) $value).'"';
    }

    /** @param array<string, mixed> $data */
    private function createAdministrator(array $data): void
    {
        foreach (PermissionRegistry::permissions() as $definition) {
            Permission::query()->updateOrCreate(
                ['name' => $definition['name']],
                ['module' => $definition['module'], 'action' => $definition['action'], 'label' => $definition['label']],
            );
        }

        $role = Role::query()->updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'description' => 'Full access to every administration feature.', 'is_system' => true],
        );
        $role->permissions()->sync(Permission::query()->pluck('id'));

        $admin = User::query()->updateOrCreate(['email' => $data['admin_email']], [
            'name' => $data['admin_name'],
            'phone' => $data['admin_phone'] ?: null,
            'password' => $data['admin_password'],
            'is_admin' => true,
            'is_active' => true,
        ]);
        $admin->forceFill([
            'email_verified_at' => now(),
            'phone_verified_at' => $data['admin_phone'] ? now() : null,
        ])->save();
        $admin->roles()->sync([$role->id]);
    }

    /** @param array<string, mixed> $data */
    private function createWebsiteSettings(array $data): void
    {
        $phone = filled($data['store_phone']) ? preg_replace('/\D+/', '', (string) $data['store_phone']) : null;
        WebsiteSetting::query()->updateOrCreate(['id' => 1], [
            'website_name' => $data['store_name'],
            'order_phone' => $phone,
            'order_whatsapp' => $phone,
            'footer_config' => [
                'description' => 'Discover quality products, great value and dependable service for everyday life.',
                'phone' => $data['store_phone'] ?: '',
                'email' => $data['store_email'] ?: '',
                'address' => '',
                'app_store_url' => '',
                'google_play_url' => '',
                'copyright_name' => $data['store_name'],
                'link_groups' => [
                    ['title' => 'Customer Care', 'links' => ['My account|/account', 'My orders|/account/orders', 'Shopping cart|/cart']],
                    ['title' => 'Shop', 'links' => ['All products|/products', 'Wishlist|/wishlist', 'Stories & Guides|/blog']],
                    ['title' => 'Company', 'links' => ['Home|/', 'Stories & Guides|/blog']],
                ],
                'social_links' => ['Facebook' => '', 'X' => '', 'Instagram' => '', 'YouTube' => '', 'TikTok' => '', 'WhatsApp' => ''],
                'payment_methods' => ['Visa', 'Mastercard', 'bKash', 'Nagad', 'NexusPay'],
                'payment_images' => [],
            ],
            'delivery_enabled' => true,
            'delivery_inside_dhaka' => $data['delivery_inside_dhaka'],
            'delivery_outside_dhaka' => $data['delivery_outside_dhaka'],
        ]);
    }

    /** @param array<string, mixed> $arguments */
    private function runCommand(string $command, array $arguments, string $failureMessage): void
    {
        if (Artisan::call($command, $arguments) !== 0) {
            throw new RuntimeException($failureMessage);
        }
    }

    private function restoreEnvironment(string $path, ?string $content): void
    {
        if ($content === null) {
            File::delete($path);

            return;
        }

        File::put($path, $content, true);
    }
}
