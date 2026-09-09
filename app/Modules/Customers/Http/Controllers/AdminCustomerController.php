<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCustomerController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $base = User::query()->where('is_admin', false);

        $customers = (clone $base)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->withCount(['addresses', 'orders'])
            ->withCount(['orders as completed_orders_count' => fn (Builder $query) => $query->where('status', 'completed')])
            ->withSum(['orders as total_spent' => fn (Builder $query) => $query->where('status', 'completed')], 'total')
            ->withMax('orders as last_order_at', 'created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'emailVerified' => $customer->email_verified_at !== null,
                'phoneVerified' => $customer->phone_verified_at !== null,
                'orders' => (int) $customer->orders_count,
                'completedOrders' => (int) $customer->completed_orders_count,
                'spent' => (float) ($customer->total_spent ?? 0),
                'addresses' => (int) $customer->addresses_count,
                'registeredAt' => $customer->created_at?->timezone('Asia/Dhaka')->format('d M Y, h:i A'),
                'lastOrderAt' => $customer->last_order_at
                    ? \Illuminate\Support\Carbon::parse($customer->last_order_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A')
                    : null,
            ]);

        return Inertia::render('app/modules/customers/pages/Index', [
            'customers' => $customers,
            'filters' => ['search' => $search],
            'stats' => [
                'registered' => (clone $base)->count(),
                'verified' => (clone $base)->where(fn (Builder $query) => $query->whereNotNull('email_verified_at')->orWhereNotNull('phone_verified_at'))->count(),
                'withOrders' => (clone $base)->whereHas('orders')->count(),
                'totalSpent' => (float) \Illuminate\Support\Facades\DB::table('orders')
                    ->join('users', 'users.id', '=', 'orders.user_id')
                    ->where('users.is_admin', false)
                    ->where('orders.status', 'completed')
                    ->sum('orders.total'),
            ],
        ]);
    }
}