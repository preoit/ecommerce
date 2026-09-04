<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardService
{
    public function page(): Response
    {
        return Inertia::render('app/modules/dashboard/pages/Index', [
            'metrics' => $this->metrics(),
            'priorities' => $this->priorities(),
        ]);
    }

    /** @return array<int, array<string, int|string>> */
    private function metrics(): array
    {
        return [
            ['label' => 'Total revenue', 'value' => 'BDT 0', 'change' => 'No sales yet', 'icon' => 'wallet'],
            ['label' => 'Orders', 'value' => 0, 'change' => 'Ready for orders', 'icon' => 'shopping-bag'],
            ['label' => 'Products', 'value' => 0, 'change' => 'Add your catalog', 'icon' => 'package'],
            ['label' => 'Customers', 'value' => User::query()->count(), 'change' => 'Registered accounts', 'icon' => 'users'],
        ];
    }

    /** @return array<int, array<string, string>> */
    private function priorities(): array
    {
        return [
            ['title' => 'Build the catalog', 'description' => 'Add categories, brands and units before creating products.', 'href' => '/admin/inventories/categories'],
            ['title' => 'Configure the store', 'description' => 'Set store identity, currency, tax and delivery preferences.', 'href' => '/admin/settings/website'],
            ['title' => 'Review the storefront', 'description' => 'Open the customer-facing store and verify the shopping experience.', 'href' => '/'],
        ];
    }
}
