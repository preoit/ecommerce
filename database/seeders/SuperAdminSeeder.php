<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SUPER_ADMIN_EMAIL', 'admin@example.com');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (! is_string($password) || mb_strlen($password) < 12) {
            throw new LogicException('SUPER_ADMIN_PASSWORD must be set and contain at least 12 characters.');
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => $password,
                'email_verified_at' => now(),
                'is_admin' => true,
            ],
        );

        $this->command?->info("Super admin account is ready: {$email}");
    }
}
