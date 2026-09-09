<?php

namespace App\Modules\Orders\Services;

use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Support\Collection;

class DeliveryChargeCalculator
{
    public function calculate(WebsiteSetting $settings, Collection $lines, float $subtotal, string $zone, string $paymentMethod): array
    {
        if (!$settings->delivery_enabled) return ['shipping' => 0.0, 'cod' => 0.0, 'total' => 0.0, 'free' => true, 'base' => 0.0, 'product_override' => 0.0, 'heavy' => 0.0];

        $base = (float) ($zone === 'inside_dhaka' ? $settings->delivery_inside_dhaka : $settings->delivery_outside_dhaka);
        $override = 0.0;
        if ($settings->product_delivery_override_enabled) {
            $field = $zone === 'inside_dhaka' ? 'delivery_inside_dhaka' : 'delivery_outside_dhaka';
            $override = (float) $lines->map(fn (array $line) => $line['product']->{$field})->filter(fn ($value) => $value !== null)->max();
        }
        $baseCharge = $override > 0 ? $override : $base;
        $totalWeight = (float) $lines->sum(fn (array $line) => ((float) ($line['product']->weight ?? 0)) * (int) $line['quantity']);
        $heavy = 0.0;
        if ($settings->heavy_delivery_enabled && $totalWeight > (float) $settings->heavy_weight_threshold) {
            $heavy = ceil($totalWeight - (float) $settings->heavy_weight_threshold) * (float) $settings->heavy_charge_per_kg;
        }
        $free = $settings->free_delivery_enabled && $settings->free_delivery_threshold !== null && $subtotal >= (float) $settings->free_delivery_threshold;
        $shipping = $free ? 0.0 : $baseCharge + $heavy;
        $cod = $settings->cod_surcharge_enabled && $paymentMethod === 'cod' ? (float) $settings->cod_surcharge : 0.0;

        return ['shipping' => round($shipping, 2), 'cod' => round($cod, 2), 'total' => round($shipping + $cod, 2), 'free' => $free, 'base' => round($base, 2), 'product_override' => round($override, 2), 'heavy' => round($heavy, 2), 'weight' => round($totalWeight, 3)];
    }
}