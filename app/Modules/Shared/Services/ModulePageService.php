<?php

namespace App\Modules\Shared\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ModulePageService
{
    public function page(Request $request): Response
    {
        $module = (string) $request->route('module');

        if ($module === 'orders') {
            return Inertia::render('app/modules/orders/pages/Index', [
                'orders' => $this->orders(),
            ]);
        }

        if ($module === 'settings') {
            return Inertia::render('app/modules/settings/pages/Index', [
                'settings' => [
                    'companyName' => config('app.name', 'Commerce Admin'),
                    'domain' => config('app.url'),
                    'allowedTypes' => '.png,.jpg,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt',
                ],
            ]);
        }

        return Inertia::render(
            'app/modules/shared/pages/ModuleOverview',
            $this->definition($module),
        );
    }

    /** @return array<int, array<string, string>> */
    private function orders(): array
    {
        if (! Schema::hasTable('orders')) {
            return [];
        }

        return DB::table('orders')->latest()->get()->map(function (object $order): array {
            $products = DB::table('order_items')->where('order_id', $order->id)->pluck('product_title')->implode(', ');
            $createdAt = \Illuminate\Support\Carbon::parse($order->created_at, 'UTC')->setTimezone('Asia/Dhaka');

            return [
                'id' => $order->id,
                'number' => $order->order_number,
                'customer' => $order->customer_name,
                'phone' => $order->phone,
                'email' => $order->email ?: '—',
                'product' => $products ?: '—',
                'delivery' => $order->address,
                'area' => $order->city,
                'note' => $order->note,
                'amount' => 'BDT '.number_format((float) $order->total, 2),
                'status' => str($order->status)->replace('_', ' ')->title()->toString(),
                'date' => $createdAt->toDateString(),
                'time' => $createdAt->format('h:i A'),
            ];
        })->all();

        return [[
            'number' => 'ORD00009',
            'customer' => 'Ashfakur Rahman',
            'phone' => '01736741793',
            'email' => 'hsmasfak@gmail.com',
            'product' => 'Cloud based restaurant POS software',
            'delivery' => 'Home jihonsjdkdi',
            'area' => 'Dhaka City',
            'amount' => '৳140.00',
            'status' => 'Processing',
            'date' => 'Aug 30, 2026',
            'time' => '01:18 AM',
        ]];
    }

    /** @return array<string, mixed> */
    private function definition(string $module): array
    {
        $definition = $this->definitions()[$module] ?? null;

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown module [{$module}].");
        }

        return [
            ...$definition,
            'moduleKey' => $module,
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function definitions(): array
    {
        return [
            'products' => [
                'eyebrow' => 'Catalog',
                'title' => 'Products',
                'description' => 'Manage sellable items, pricing, variants and product visibility.',
                'actions' => [['label' => 'Add product', 'href' => '#', 'icon' => 'plus']],
                'features' => ['Product information', 'Pricing and variants', 'Images and SEO', 'Publishing status'],
            ],
            'categories' => [
                'eyebrow' => 'Inventories',
                'title' => 'Categories',
                'description' => 'Organize products into a clear, searchable category hierarchy.',
                'actions' => [['label' => 'Add category', 'href' => '#', 'icon' => 'plus']],
                'features' => ['Nested categories', 'Display order', 'SEO metadata', 'Active status'],
            ],
            'brands' => [
                'eyebrow' => 'Inventories',
                'title' => 'Brands',
                'description' => 'Maintain product brands and their storefront presentation.',
                'actions' => [['label' => 'Add brand', 'href' => '#', 'icon' => 'plus']],
                'features' => ['Brand identity', 'Logo management', 'SEO metadata', 'Active status'],
            ],
            'units' => [
                'eyebrow' => 'Inventories',
                'title' => 'Units',
                'description' => 'Define reusable measurement units for product inventory.',
                'actions' => [['label' => 'Add unit', 'href' => '#', 'icon' => 'plus']],
                'features' => ['Unit name', 'Short code', 'Precision', 'Active status'],
            ],
            'orders' => [
                'eyebrow' => 'Sales',
                'title' => 'Orders',
                'description' => 'Process customer orders from placement through delivery.',
                'actions' => [],
                'features' => ['Order workflow', 'Payment status', 'Fulfilment', 'Customer communication'],
            ],
            'customers' => [
                'eyebrow' => 'Relationships',
                'title' => 'Customers',
                'description' => 'View customer accounts, order history and contact details.',
                'actions' => [],
                'features' => ['Customer profiles', 'Order history', 'Addresses', 'Account status'],
            ],
            'coupons' => [
                'eyebrow' => 'Marketing',
                'title' => 'Coupons',
                'description' => 'Create controlled discounts with clear eligibility and usage rules.',
                'actions' => [['label' => 'Add coupon', 'href' => '#', 'icon' => 'plus']],
                'features' => ['Discount rules', 'Usage limits', 'Validity period', 'Customer eligibility'],
            ],
            'reviews' => [
                'eyebrow' => 'Trust',
                'title' => 'Reviews',
                'description' => 'Moderate product feedback and maintain storefront quality.',
                'actions' => [],
                'features' => ['Moderation queue', 'Verified purchases', 'Ratings', 'Published status'],
            ],
            'reports' => [
                'eyebrow' => 'Analytics',
                'title' => 'Reports',
                'description' => 'Track sales, customers, products and inventory performance.',
                'actions' => [['label' => 'Export report', 'href' => '#', 'icon' => 'download']],
                'features' => ['Sales trends', 'Product performance', 'Customer insights', 'Inventory health'],
            ],
            'settings' => [
                'eyebrow' => 'Configuration',
                'title' => 'Settings',
                'description' => 'Control store identity, commerce rules and operational preferences.',
                'actions' => [],
                'features' => ['Store profile', 'Currency and tax', 'Delivery', 'Notifications'],
            ],
        ];
    }
}
