<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderDetailsController extends Controller
{
    public function updateStatus(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,confirmed,processing,ready_to_ship,shipped,completed,cancelled,returned']]);
        abort_unless(DB::table('orders')->where('id', $order)->exists(), 404);
        DB::table('orders')->where('id', $order)->update(['status' => $data['status'], 'updated_at' => now()]);

        return back()->with('success', 'Order status updated.');
    }

    public function show(int $order): Response
    {
        $record = DB::table('orders')->find($order);
        abort_unless($record, 404);
        if ($record->viewed_at === null) {
            DB::table('orders')->where('id', $record->id)->update(['viewed_at' => now(), 'updated_at' => now()]);
            $record->viewed_at = now();
        }
        $createdAt = Carbon::parse($record->created_at, 'UTC')->setTimezone('Asia/Dhaka');
        $customerOrderCount = $record->user_id
            ? DB::table('orders')->where('user_id', $record->user_id)->count()
            : DB::table('orders')->where('phone', $record->phone)->count();
        $items = DB::table('order_items')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('order_items.order_id', $record->id)
            ->select('order_items.*', 'products.featured_image_path', 'products.slug')
            ->get();

        return Inertia::render('app/modules/orders/pages/Show', [
            'order' => [
                'id' => $record->id, 'number' => $record->order_number, 'customerName' => $record->customer_name,
                'customerOrderCount' => $customerOrderCount,
                'phone' => $record->phone, 'email' => $record->email, 'address' => $record->address, 'city' => $record->city,
                'note' => $record->note, 'paymentMethod' => $record->payment_method, 'paymentStatus' => $record->payment_status,
                'status' => str($record->status)->replace('_', ' ')->title()->toString(), 'statusKey' => $record->status, 'subtotal' => (float) $record->subtotal, 'shippingTotal' => (float) $record->shipping_total, 'codSurcharge' => (float) ($record->cod_surcharge ?? 0), 'deliveryZone' => $record->delivery_zone ?? null,
                'total' => (float) $record->total, 'date' => $createdAt->format('d M Y, h:i A'), 'hasStockShortage' => (bool) ($record->has_stock_shortage ?? false),
                'items' => $items->map(fn (object $item): array => [
                    'title' => $item->product_title, 'variantName' => $item->variant_name ?? null, 'sku' => $item->sku, 'quantity' => $item->quantity,
                    'unitPrice' => (float) $item->unit_price, 'lineTotal' => (float) $item->line_total, 'stockShortageQuantity' => (int) ($item->stock_shortage_quantity ?? 0),
                    'image' => $item->featured_image_path ? asset('storage/'.$item->featured_image_path) : null,
                    'slug' => $item->slug,
                ]),
            ],
        ]);
    }
}
