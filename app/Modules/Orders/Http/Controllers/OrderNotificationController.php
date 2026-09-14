<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = DB::table('orders')->latest()->limit(20)->get()->map(fn (object $order): array => [
            'id' => $order->id,
            'number' => $order->order_number,
            'customer' => $order->customer_name,
            'total' => (float) $order->total,
            'status' => str($order->status)->replace('_', ' ')->title()->toString(),
            'phone' => $order->phone,
            'viewed' => $order->viewed_at !== null,
            'hasStockShortage' => (bool) $order->has_stock_shortage,
            'createdAt' => Carbon::parse($order->created_at, 'UTC')->setTimezone('Asia/Dhaka')->diffForHumans(),
            'date' => Carbon::parse($order->created_at, 'UTC')->setTimezone('Asia/Dhaka')->format('d M Y, h:i A'),
        ]);

        return response()->json([
            'orders' => $orders,
            'unreadCount' => DB::table('orders')->whereNull('viewed_at')->count(),
        ]);
    }

    public function markViewed(int $order): JsonResponse
    {
        DB::table('orders')->where('id', $order)->whereNull('viewed_at')->update(['viewed_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
