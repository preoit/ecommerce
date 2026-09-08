<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Inventories\Brands\Models\Brand;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardService
{
    public function page(): Response
    {
        return Inertia::render('app/modules/dashboard/pages/Index', [
            'summary' => $this->summary(),
            'orderStatus' => $this->orderStatus(),
            'recentOrders' => $this->recentOrders(),
            'stockAlerts' => $this->stockAlerts(),
            'catalog' => $this->catalog(),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function summary(): array
    {
        $totalRevenue = $this->hasTable('orders') ? (float) DB::table('orders')->sum('total') : 0;
        $todayRevenue = $this->hasTable('orders') ? (float) DB::table('orders')->whereDate('created_at', today())->sum('total') : 0;
        $orders = $this->hasTable('orders') ? DB::table('orders')->count() : 0;
        $newOrders = $this->hasTable('orders') && Schema::hasColumn('orders', 'viewed_at') ? DB::table('orders')->whereNull('viewed_at')->count() : 0;
        $products = $this->hasTable('products') ? Product::query()->count() : 0;
        $published = $this->hasTable('products') ? Product::query()->whereIn('status', ['Active', 'Published', 'active', 'published'])->count() : 0;
        $customers = User::query()->count();
        $stockRisk = $this->stockRiskCount();

        return [
            ['label' => 'Total revenue', 'value' => $this->money($totalRevenue), 'hint' => $this->money($todayRevenue).' today', 'icon' => 'revenue', 'href' => '/admin/orders'],
            ['label' => 'Orders', 'value' => number_format($orders), 'hint' => $newOrders.' unviewed', 'icon' => 'orders', 'href' => '/admin/orders'],
            ['label' => 'Products', 'value' => number_format($products), 'hint' => $published.' active', 'icon' => 'products', 'href' => '/admin/inventories/products'],
            ['label' => 'Customers', 'value' => number_format($customers), 'hint' => $stockRisk.' stock alerts', 'icon' => 'customers', 'href' => '/admin/customers'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function orderStatus(): array
    {
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];

        if (! $this->hasTable('orders')) {
            return collect($statuses)->map(fn (string $status): array => $this->statusRow($status, 0))->all();
        }

        $counts = DB::table('orders')->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return collect($statuses)->map(fn (string $status): array => $this->statusRow($status, (int) ($counts[$status] ?? 0)))->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function recentOrders(): array
    {
        if (! $this->hasTable('orders')) {
            return [];
        }

        return DB::table('orders')->latest()->limit(6)->get()->map(function (object $order): array {
            $createdAt = Carbon::parse($order->created_at, 'UTC')->setTimezone('Asia/Dhaka');
            $viewed = Schema::hasColumn('orders', 'viewed_at') ? $order->viewed_at !== null : true;

            return [
                'id' => $order->id,
                'number' => $order->order_number,
                'customer' => $order->customer_name,
                'phone' => $order->phone,
                'total' => $this->money((float) $order->total),
                'status' => str($order->status)->replace('_', ' ')->title()->toString(),
                'statusKey' => $order->status,
                'viewed' => $viewed,
                'hasStockShortage' => (bool) ($order->has_stock_shortage ?? false),
                'date' => $createdAt->format('d M, h:i A'),
                'href' => '/admin/orders/'.$order->id,
            ];
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function stockAlerts(): array
    {
        if (! $this->hasTable('products')) {
            return [];
        }

        $hasThreshold = Schema::hasColumn('products', 'low_stock_threshold');
        $query = Product::query()->select($hasThreshold
            ? ['id', 'title', 'sku', 'stock_quantity', 'low_stock_threshold', 'status']
            : ['id', 'title', 'sku', 'stock_quantity', 'status']
        );

        $query = $hasThreshold
            ? $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            : $query->where('stock_quantity', '<=', 0);

        return $query->orderBy('stock_quantity')->limit(6)->get()->map(fn (Product $product): array => [
            'id' => $product->id,
            'title' => $product->title,
            'sku' => $product->sku ?: 'No SKU',
            'stock' => (int) $product->stock_quantity,
            'threshold' => $hasThreshold ? (int) $product->low_stock_threshold : 0,
            'status' => $product->stock_status,
            'href' => '/admin/inventories/products/create?edit='.$product->id,
        ])->all();
    }

    /** @return array<string, mixed> */
    private function catalog(): array
    {
        return [
            'categories' => $this->hasTable('categories') ? Category::query()->count() : 0,
            'brands' => $this->hasTable('brands') ? Brand::query()->count() : 0,
            'drafts' => $this->hasTable('products') ? Product::query()->whereIn('status', ['Draft', 'draft'])->count() : 0,
            'outOfStock' => $this->hasTable('products') ? Product::query()->where('stock_quantity', '<=', 0)->count() : 0,
        ];
    }

    /** @return array<string, mixed> */
    private function statusRow(string $status, int $count): array
    {
        return [
            'key' => $status,
            'label' => str($status)->replace('_', ' ')->title()->toString(),
            'count' => $count,
        ];
    }

    private function stockRiskCount(): int
    {
        if (! $this->hasTable('products')) {
            return 0;
        }

        return Schema::hasColumn('products', 'low_stock_threshold')
            ? Product::query()->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count()
            : Product::query()->where('stock_quantity', '<=', 0)->count();
    }

    private function money(float $amount): string
    {
        return 'BDT '.number_format($amount, 2);
    }

    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }
}