<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QuickCustomerController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/', Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'city' => ['required', 'string', 'max:120'],
            'address_label' => ['required', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:1000'],
        ]);

        $customer = DB::transaction(function () use ($data): User {
            $customer = User::query()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => filled($data['email'] ?? null) ? $data['email'] : null,
                'password' => Str::random(48),
                'is_admin' => false,
            ]);
            $customer->addresses()->create([
                'label' => $data['address_label'],
                'recipient_name' => $data['name'],
                'phone' => $data['phone'],
                'delivery_zone' => str_contains(strtolower($data['city']), 'dhaka') ? 'inside_dhaka' : 'outside_dhaka',
                'district' => $data['city'],
                'city' => $data['city'],
                'address' => $data['address'],
                'is_default' => true,
            ]);

            return $customer;
        });

        return response()->json(['customer' => [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'city' => $data['city'],
            'address' => $data['address'],
            'addressLabel' => $data['address_label'],
        ]], 201);
    }
}
