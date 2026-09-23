<?php

namespace App\Support;

use Illuminate\Http\Request;

final class PermissionRegistry
{
    /** @return array<string, array{label:string, actions:array<string,string>}> */
    public static function modules(): array
    {
        $crud = ['view' => 'View', 'create' => 'Create', 'update' => 'Edit', 'delete' => 'Delete'];

        return [
            'dashboard' => ['label' => 'Dashboard', 'actions' => ['view' => 'View']],
            'products' => ['label' => 'Products', 'actions' => $crud],
            'categories' => ['label' => 'Categories', 'actions' => $crud],
            'brands' => ['label' => 'Brands', 'actions' => $crud],
            'units' => ['label' => 'Units', 'actions' => $crud],
            'orders' => ['label' => 'Orders', 'actions' => $crud],
            'customers' => ['label' => 'Customers', 'actions' => $crud],
            'coupons' => ['label' => 'Coupons', 'actions' => $crud],
            'reviews' => ['label' => 'Reviews', 'actions' => $crud],
            'reports' => ['label' => 'Reports', 'actions' => ['view' => 'View']],
            'file_manager' => ['label' => 'File Manager', 'actions' => $crud],
            'blog' => ['label' => 'Blog', 'actions' => $crud],
            'courier' => ['label' => 'Courier', 'actions' => $crud],
            'website_design' => ['label' => 'Website Design', 'actions' => ['view' => 'View', 'update' => 'Edit']],
            'settings' => ['label' => 'Settings', 'actions' => ['view' => 'View', 'update' => 'Edit']],
            'users' => ['label' => 'Admin Users', 'actions' => $crud],
            'roles' => ['label' => 'Roles & Permissions', 'actions' => $crud],
        ];
    }

    /** @return array<int, array{name:string,module:string,action:string,label:string,module_label:string}> */
    public static function permissions(): array
    {
        $permissions = [];
        foreach (self::modules() as $module => $definition) {
            foreach ($definition['actions'] as $action => $label) {
                $permissions[] = [
                    'name' => "{$module}.{$action}",
                    'module' => $module,
                    'action' => $action,
                    'label' => $label,
                    'module_label' => $definition['label'],
                ];
            }
        }

        return $permissions;
    }

    public static function requiredFor(Request $request): ?string
    {
        $name = (string) optional($request->route())->getName();
        $module = match (true) {
            $name === 'dashboard' => 'dashboard',
            str_starts_with($name, 'inventories.products.') => 'products',
            str_starts_with($name, 'inventories.categories.') => 'categories',
            str_starts_with($name, 'inventories.brands.') => 'brands',
            str_starts_with($name, 'inventories.units.') => 'units',
            str_starts_with($name, 'orders.') => 'orders',
            str_starts_with($name, 'customers.') => 'customers',
            str_starts_with($name, 'coupons.') => 'coupons',
            str_starts_with($name, 'reviews.') => 'reviews',
            str_starts_with($name, 'questions.') => 'reviews',
            str_starts_with($name, 'reports.') => 'reports',
            str_starts_with($name, 'file-manager.') => 'file_manager',
            str_starts_with($name, 'blog.') => 'blog',
            str_starts_with($name, 'couriers.') => 'courier',
            str_starts_with($name, 'website-design.') => 'website_design',
            str_starts_with($name, 'settings.') => 'settings',
            str_starts_with($name, 'admin-users.') => 'users',
            str_starts_with($name, 'roles.') => 'roles',
            default => null,
        };

        if ($module === null) return null;
        if ($name === 'orders.view') return 'orders.view';

        $action = match (true) {
            str_contains($name, '.destroy'), str_contains($name, '.delete') => 'delete',
            str_contains($name, '.create'), str_contains($name, '.store') => 'create',
            str_contains($name, '.edit'), str_contains($name, '.update'), str_contains($name, '.upload'),
            str_contains($name, '.generate'), str_contains($name, '.test-') => 'update',
            default => match ($request->method()) {
                'DELETE' => 'delete',
                'POST' => 'create',
                'PUT', 'PATCH' => 'update',
                default => 'view',
            },
        };

        $available = self::modules()[$module]['actions'];
        if (! array_key_exists($action, $available)) {
            $action = array_key_exists('update', $available) && $action !== 'view' ? 'update' : 'view';
        }

        return "{$module}.{$action}";
    }
}
