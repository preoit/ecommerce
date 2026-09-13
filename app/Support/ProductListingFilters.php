<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductListingFilters
{
    private const PRICE = 'CASE WHEN sale_price IS NOT NULL AND sale_price < regular_price THEN sale_price ELSE regular_price END';

    public static function apply(Builder $query, Request $request): array
    {
        $filters = $request->validate([
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', ...($request->filled('min_price') ? ['gte:min_price'] : [])],
            'in_stock' => ['nullable', 'boolean'],
        ]);
        $bounds = (clone $query)->reorder()->selectRaw('MIN('.self::PRICE.') AS minimum, MAX('.self::PRICE.') AS maximum')->first();
        foreach (['min_price' => '>=', 'max_price' => '<='] as $key => $operator) {
            if (isset($filters[$key])) {
                $query->whereRaw(self::PRICE." {$operator} CAST(? AS DECIMAL(20, 2))", [$filters[$key]]);
            }
        }
        if ($request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        return ['min' => floor((float) $bounds?->minimum), 'max' => ceil((float) $bounds?->maximum)];
    }
}
