<?php

namespace App\Http\Controllers;

use App\Modules\Inventories\Products\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StorefrontProductSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $search = Str::of((string) $request->query('q', ''))
            ->stripTags()
            ->squish()
            ->limit(80, '')
            ->toString();

        if (mb_strlen($search) < 2) {
            return response()->json(['query' => $search, 'suggestions' => []]);
        }

        $cacheKey = 'storefront.search-suggestions.'.sha1(mb_strtolower($search));
        $suggestions = Cache::remember($cacheKey, now()->addMinute(), function () use ($search): array {
            $pattern = "%{$search}%";
            $prefix = "{$search}%";

            return Product::query()
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->whereIn('products.status', ['Published', 'Active'])
                ->where('products.visibility', 'Public')
                ->where(function (Builder $query) use ($pattern): void {
                    $query->where('products.title', 'like', $pattern)
                        ->orWhere('products.sku', 'like', $pattern)
                        ->orWhere('products.barcode', 'like', $pattern)
                        ->orWhere('products.model', 'like', $pattern)
                        ->orWhere('brands.name', 'like', $pattern)
                        ->orWhereHas('categories', fn (Builder $category) => $category->where('categories.name', 'like', $pattern));
                })
                ->orderByRaw('CASE WHEN products.sku = ? THEN 0 WHEN products.title LIKE ? THEN 1 ELSE 2 END', [$search, $prefix])
                ->orderBy('products.title')
                ->limit(8)
                ->get([
                    'products.id', 'products.title', 'products.slug', 'products.sku',
                    'products.regular_price', 'products.sale_price', 'products.featured_image_path',
                    'brands.name as brand_name',
                ])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'brand' => $product->brand_name,
                    'price' => $product->sale_price !== null && (float) $product->sale_price < (float) $product->regular_price
                        ? (float) $product->sale_price
                        : (float) $product->regular_price,
                    'image' => $product->featured_image_path ? '/image/'.rawurlencode(basename($product->featured_image_path)) : null,
                ])
                ->all();
        });

        return response()->json(['query' => $search, 'suggestions' => $suggestions]);
    }
}
