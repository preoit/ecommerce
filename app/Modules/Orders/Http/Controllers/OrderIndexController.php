<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,confirmed,processing,ready_to_ship,shipped,completed,cancelled,returned'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
        ]);

        $query = DB::table('orders')->when(filled($filters['q'] ?? null), function ($query) use ($filters): void {
            $term = '%'.addcslashes($filters['q'], '%_\\').'%';
            $query->where(fn ($nested) => $nested->where('order_number', 'like', $term)->orWhere('customer_name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('email', 'like', $term));
        })->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->orderByDesc('id');

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 25))->withQueryString();
        $orderIds = collect($paginator->items())->pluck('id');
        $itemsByOrder = DB::table('order_items')->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('order_items.order_id', $orderIds)->get(['order_items.order_id', 'order_items.product_title', 'order_items.variant_name', 'order_items.quantity', 'products.slug'])->groupBy('order_id');

        $orders = collect($paginator->items())->map(function (object $order) use ($itemsByOrder): array {
            $items = $itemsByOrder->get($order->id, collect());
            $createdAt = Carbon::parse($order->created_at, 'UTC')->setTimezone('Asia/Dhaka');
            return [
                'id' => $order->id, 'number' => $order->order_number, 'customer' => $order->customer_name,
                'phone' => $order->phone, 'email' => $order->email ?: '—', 'itemCount' => (int) $items->sum('quantity'),
                'products' => $items->map(fn (object $item): array => ['name' => $item->product_title, 'variant' => $item->variant_name, 'quantity' => (int) $item->quantity, 'slug' => $item->slug])->values(),
                'delivery' => $order->address, 'note' => $order->note, 'subtotal' => (float) $order->subtotal,
                'shipping' => (float) $order->shipping_total, 'deliveryZone' => $order->delivery_zone ?? null,
                'codSurcharge' => (float) ($order->cod_surcharge ?? 0), 'total' => (float) $order->total,
                'status' => str($order->status)->replace('_', ' ')->title()->toString(), 'statusKey' => $order->status,
                'viewed' => $order->viewed_at !== null, 'date' => $createdAt->toDateString(), 'time' => $createdAt->format('h:i A'),
                'canEdit' => in_array($order->status, ['pending', 'confirmed', 'processing'], true),
                'canDelete' => $order->status === 'pending' && $order->payment_status !== 'paid',
            ];
        })->values();

        return Inertia::render('app/modules/orders/pages/Index', [
            'orders' => $orders,
            'filters' => ['q' => $filters['q'] ?? '', 'status' => $filters['status'] ?? '', 'dateFrom' => $filters['date_from'] ?? '', 'dateTo' => $filters['date_to'] ?? '', 'perPage' => (int) ($filters['per_page'] ?? 25)],
            'statusCounts' => DB::table('orders')->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
            'pagination' => ['currentPage' => $paginator->currentPage(), 'lastPage' => $paginator->lastPage(), 'perPage' => $paginator->perPage(), 'total' => $paginator->total(), 'from' => $paginator->firstItem(), 'to' => $paginator->lastItem()],
        ]);
    }
}
