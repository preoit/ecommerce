<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionRegistry;
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

        foreach (PermissionRegistry::permissions() as $definition) {
            Permission::query()->updateOrCreate(['name'=>$definition['name']], ['module'=>$definition['module'],'action'=>$definition['action'],'label'=>$definition['label']]);
        }
        $role = Role::query()->updateOrCreate(['slug'=>'super-admin'], ['name'=>'Super Admin','description'=>'Full access to every administration feature.','is_system'=>true]);
        $role->permissions()->sync(Permission::pluck('id'));

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => $password,
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_active' => true,
            ],
        );
        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->command?->info("Super admin account is ready: {$email}");
    }
}
