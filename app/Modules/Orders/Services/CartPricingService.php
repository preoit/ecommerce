<?php

namespace App\Modules\Orders\Services;

use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Inventories\Products\Models\ProductVariant;

class CartPricingService
{
    public function unitPrice(Product $product, ?ProductVariant $variant, int $quantity): float
    {
        if ($variant) {
            return round((float) $variant->current_price, 2);
        }

        $bulkPrice = $product->relationLoaded('bulkPrices')
            ? $product->bulkPrices
                ->filter(fn ($tier) => $tier->min_quantity <= $quantity && ($tier->max_quantity === null || $tier->max_quantity >= $quantity))
                ->sortByDesc('min_quantity')
                ->first()?->unit_price
            : $product->bulkPrices()
                ->where('min_quantity', '<=', $quantity)
                ->where(fn ($query) => $query->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity))
                ->orderByDesc('min_quantity')
                ->value('unit_price');

        return round((float) ($bulkPrice ?? $product->current_price), 2);
    }
}
