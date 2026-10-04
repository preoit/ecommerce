<?php

namespace App\Services;

use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StorefrontHomepageService
{
    /** @return array{featuredProducts: array<int, array>, categorySections: array<int, array>} */
    public function payload(): array
    {
        return Cache::remember('storefront.home_category_sections.v1', now()->addMinutes(10), function (): array {
            $configured = collect(WebsiteSetting::query()->find(1)?->homepage_category_sections ?? [])
                ->filter(fn ($section): bool => is_array($section) && (bool) ($section['enabled'] ?? true))
                ->values();

            if ($configured->isEmpty()) {
                return [
                    'featuredProducts' => $this->baseProductQuery()->latest('published_at')->latest('id')->limit(8)->get()->map(fn (Product $product): array => $this->card($product))->all(),
                    'categorySections' => [],
                ];
            }

            $categories = Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->whereIn('id', $configured->pluck('category_id')->map(fn ($id): int => (int) $id))
                ->get(['id', 'parent_id', 'name', 'slug', 'short_description'])
                ->keyBy('id');
            $allCategories = Category::query()->where('is_active', true)->get(['id', 'parent_id']);
            $children = $allCategories->groupBy('parent_id');

            $sections = $configured->map(function (array $section) use ($categories, $children): ?array {
                $category = $categories->get((int) ($section['category_id'] ?? 0));
                if (! $category) return null;

                $query = $this->baseProductQuery()
                    ->whereHas('categories', fn (Builder $query): Builder => $query->whereIn('categories.id', $this->descendantIds($category->id, $children)));
                $this->sortProducts($query, (string) ($section['sort'] ?? 'latest'));
                $products = $query->limit(min(16, max(4, (int) ($section['limit'] ?? 8))))->get();
                if ($products->isEmpty()) return null;

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => Str::limit(trim(strip_tags((string) $category->short_description)), 150),
                    'products' => $products->map(fn (Product $product): array => $this->card($product, $category->name))->all(),
                ];
            })->filter()->values()->all();

            return ['featuredProducts' => [], 'categorySections' => $sections];
        });
    }

    private function baseProductQuery(): Builder
    {
        return Product::query()
            ->with(['categories:id,name', 'brand:id,name'])
            ->whereIn('status', ['Published', 'Active'])
            ->where('visibility', 'Public');
    }

    /** @param Collection<int, Collection<int, Category>> $children */
    private function descendantIds(int $categoryId, Collection $children): array
    {
        $ids = [$categoryId];
        foreach ($children->get($categoryId, collect()) as $child) {
            array_push($ids, ...$this->descendantIds($child->id, $children));
        }

        return $ids;
    }

    private function sortProducts(Builder $query, string $sort): void
    {
        match ($sort) {
            'featured' => $query->orderByDesc('is_featured')->latest('published_at')->latest('id'),
            'best_seller' => $query->orderByDesc('is_best_seller')->latest('published_at')->latest('id'),
            default => $query->latest('published_at')->latest('id'),
        };
    }

    private function card(Product $product, ?string $fallbackCategory = null): array
    {
        return [
            'id' => $product->id,
            'name' => $product->title,
            'slug' => $product->slug,
            'category' => $product->categories->first()?->name ?? $fallbackCategory ?? 'Products',
            'brand' => $product->brand?->name,
            'price' => (float) $product->current_price,
            'regularPrice' => (float) $product->regular_price,
            'discount' => $product->discount_percentage,
            'stockStatus' => $product->stock_status,
            'isNewArrival' => $product->is_new_arrival,
            'image' => $product->featured_image_path ? '/image/'.rawurlencode(basename($product->featured_image_path)) : null,
        ];
    }
}
