<?php

namespace App\Http\Controllers;

use App\Modules\Inventories\Brands\Models\Brand;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use App\Support\ProductListingFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontProductListingController extends Controller
{
    public function brand(Request $request, Brand $brand): Response
    {
        abort_unless($brand->is_active, 404);
        $request->query->set('brand', $brand->slug);

        return $this($request);
    }

    public function __invoke(Request $request): Response
    {
        $search = Str::of((string) $request->query('search', ''))
            ->stripTags()
            ->squish()
            ->limit(120, '')
            ->toString();
        $searchTerms = collect(preg_split('/\s+/u', $search) ?: [])
            ->filter()
            ->unique()
            ->take(8)
            ->values();

        $products = Product::query()
            ->with(['brand:id,name,slug', 'categories:id,name,slug'])
            ->whereIn('status', ['Published', 'Active'])
            ->where('visibility', 'Public')
            ->when($searchTerms->isNotEmpty(), function (Builder $query) use ($searchTerms): void {
                $searchTerms->each(function (string $term) use ($query): void {
                    $pattern = "%{$term}%";
                    $query->where(function (Builder $query) use ($pattern): void {
                        $query->where('title', 'like', $pattern)
                            ->orWhere('sku', 'like', $pattern)
                            ->orWhere('barcode', 'like', $pattern)
                            ->orWhere('model', 'like', $pattern)
                            ->orWhere('manufacturer', 'like', $pattern)
                            ->orWhere('short_description', 'like', $pattern)
                            ->orWhere('description', 'like', $pattern)
                            ->orWhere('tags', 'like', $pattern)
                            ->orWhere('focus_keyword', 'like', $pattern)
                            ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $pattern))
                            ->orWhereHas('categories', fn (Builder $category) => $category->where('name', 'like', $pattern))
                            ->orWhereHas('variants', fn (Builder $variant) => $variant
                                ->where('is_active', true)
                                ->where(fn (Builder $variant) => $variant->where('name', 'like', $pattern)->orWhere('sku', 'like', $pattern)));
                    });
                });
            })
            ->when($request->string('category')->trim()->toString(), fn (Builder $query, string $slug) => $query->whereHas('categories', fn (Builder $category) => $category->where('slug', $slug)))
            ->when($request->string('brand')->trim()->toString(), fn (Builder $query, string $slug) => $query->whereHas('brand', fn (Builder $brand) => $brand->where('slug', $slug)));

        $priceBounds = ProductListingFilters::apply($products, $request);
        $products = $products->latest('published_at')->latest('id')->paginate(16)->withQueryString();
        $products->through(fn (Product $product): array => $this->cardData($product));

        $categorySlug = $request->string('category')->trim()->toString();
        $brandSlug = $request->string('brand')->trim()->toString();
        $seoColumns = ['short_description', 'seo_title', 'meta_description', 'meta_robots', 'canonical_url', 'og_title', 'og_description'];
        $listingSeo = $brandSlug
            ? Brand::query()->where('slug', $brandSlug)->first($seoColumns)
            : ($categorySlug ? Category::query()->where('slug', $categorySlug)->first($seoColumns) : null);

        return Inertia::render('app/modules/storefront/products/pages/Index', [
            'products' => $products,
            'filters' => [...$request->only(['category', 'brand', 'min_price', 'max_price', 'in_stock']), 'search' => $search],
            'priceBounds' => $priceBounds,
            'listingSeo' => $listingSeo,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    private function cardData(Product $product): array
    {
        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'image' => $product->featured_image_path ? '/image/'.rawurlencode(basename($product->featured_image_path)) : null,
            'price' => $product->current_price,
            'regularPrice' => (float) $product->regular_price,
            'discount' => $product->discount_percentage,
            'stockStatus' => $product->stock_status,
            'brand' => $product->brand?->name,
        ];
    }
}
