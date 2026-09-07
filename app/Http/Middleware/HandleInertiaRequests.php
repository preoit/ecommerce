<?php

namespace App\Http\Middleware;

use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Settings\Models\WebsiteMedia;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
            ],
            'website' => fn (): array => $this->websiteIdentity(),
            'storefrontCategories' => fn (): array => $this->storefrontCategoryTree(),
            'cartCount' => fn (): int => collect($request->session()->get('cart', []))->sum('quantity'),
        ];
    }

    private function websiteIdentity(): array
    {
        $settings = Schema::hasTable('website_settings') ? WebsiteSetting::find(1) : null;
        $assetPaths = collect([
            $settings?->logo_path,
            $settings?->favicon_path,
            $settings?->seo_image_path,
            $settings?->hero_primary_image_path,
            ...($settings?->hero_primary_image_paths ?: []),
            $settings?->hero_secondary_image_path,
        ])->filter()->unique()->values();
        $mediaByPath = Schema::hasTable('website_media')
            ? WebsiteMedia::query()->whereIn('path', $assetPaths)->get()->keyBy('path')
            : collect();
        $assetUrl = static fn (?string $path): ?string => $path && Storage::disk('public')->exists($path)
            ? $mediaByPath->get($path)?->publicUrl()
            : null;

        return [
            'name' => $settings?->website_name ?: null,
            'logo' => $assetUrl($settings?->logo_path),
            'favicon' => $assetUrl($settings?->favicon_path),
            'seoTitle' => $settings?->seo_title,
            'seoDescription' => $settings?->seo_description,
            'seoImage' => $assetUrl($settings?->seo_image_path),
            'heroPrimaryImage' => $assetUrl($settings?->hero_primary_image_path),
            'heroPrimaryImages' => collect($settings?->hero_primary_image_paths ?: array_filter([$settings?->hero_primary_image_path]))
                ->map(fn (string $path): string => $assetUrl($path))->values()->all(),
            'heroPrimaryLink' => $settings?->hero_primary_link,
            'heroSecondaryImage' => $assetUrl($settings?->hero_secondary_image_path),
            'heroSecondaryLink' => $settings?->hero_secondary_link,
            'footer' => $settings?->footer_config,
            'allowOutOfStockOrders' => (bool) ($settings?->allow_out_of_stock_orders ?? true),
            'showStockToCustomers' => (bool) ($settings?->show_stock_to_customers ?? false),
        ];
    }

    /** @return array<int, array{id: int, name: string, slug: string, children: array}> */
    private function storefrontCategoryTree(): array
    {
        if (! Schema::hasTable('categories')) {
            return [];
        }
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug'])
            ->groupBy('parent_id');

        $branch = function (?int $parentId) use (&$branch, $categories): array {
            return $categories->get($parentId, collect())->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'children' => $branch($category->id),
            ])->values()->all();
        };

        return $branch(null);
    }
}
