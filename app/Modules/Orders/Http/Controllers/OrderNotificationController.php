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
            'viewed' => $order->viewed_at !== null,
            'createdAt' => Carbon::parse($order->created_at, 'UTC')->setTimezone('Asia/Dhaka')->diffForHumans(),
        ]);

        return response()->json([
            'orders' => $orders,
            'unreadCount' => $orders->where('viewed', false)->count(),
        ]);
    }

    public function markViewed(int $order): JsonResponse
    {
        DB::table('orders')->where('id', $order)->whereNull('viewed_at')->update(['viewed_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
