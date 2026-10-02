<?php

namespace App\Modules\Inventories\Categories\Services;

use App\Modules\Inventories\Categories\Actions\CreateCategoryAction;
use App\Modules\Inventories\Categories\Actions\StoreCategoryImageAction;
use App\Modules\Inventories\Categories\DTOs\CategoryData;
use App\Modules\Inventories\Categories\Http\Requests\StoreCategoryRequest;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use App\Support\Content\RichTextSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as CollectionPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryService
{
    public function __construct(
        private readonly CreateCategoryAction $createCategory,
        private readonly StoreCategoryImageAction $storeCategoryImage,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    public function page(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('app/modules/inventories/categories/pages/Index', [
            'categories' => $this->paginate($search),
            'parentOptions' => $this->parentOptions(),
            'filters' => ['search' => $search],
        ]);
    }

    public function form(?Category $category = null): Response
    {
        return Inertia::render('app/modules/inventories/categories/pages/Form', [
            'category' => $category,
            'parentOptions' => $this->parentOptions(),
        ]);
    }
    public function publicPage(Request $request, Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $category->load([
            'parent:id,name,slug',
            'children' => fn ($query) => $query->where('is_active', true),
        ]);

        $allCategories = Category::query()->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug']);
        $childrenByParent = $allCategories->groupBy('parent_id');
        $descendantIds = function (int $parentId) use (&$descendantIds, $childrenByParent): array {
            $ids = [$parentId];
            foreach ($childrenByParent->get($parentId, collect()) as $child) {
                array_push($ids, ...$descendantIds($child->id));
            }
            return $ids;
        };
        $categoryIds = collect($descendantIds($category->id));
        $subcategories = [];
        $appendSubcategories = function (int $parentId, int $depth) use (&$appendSubcategories, &$subcategories, $childrenByParent, $descendantIds): void {
            foreach ($childrenByParent->get($parentId, collect()) as $child) {
                $subcategories[] = [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'depth' => $depth,
                    'categoryIds' => $descendantIds($child->id),
                ];
                $appendSubcategories($child->id, $depth + 1);
            }
        };
        $appendSubcategories($category->id, 0);

        $brandSlug = $request->string('brand')->trim()->toString();
        $brandIds = Product::query()
            ->whereIn('status', ['Published', 'Active'])
            ->where('visibility', 'Public')
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds->unique()))
            ->whereNotNull('brand_id')
            ->distinct()
            ->pluck('brand_id');
        $brands = \App\Modules\Inventories\Brands\Models\Brand::query()
            ->whereIn('id', $brandIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $products = Product::query()
            ->with(['brand:id,name', 'categories:id,name'])
            ->whereIn('status', ['Published', 'Active'])
            ->where('visibility', 'Public')
            ->when($brandSlug, fn ($query, $slug) => $query->whereHas('brand', fn ($brandQuery) => $brandQuery->where('slug', $slug)))
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds->unique()));
        $priceBounds = \App\Support\ProductListingFilters::apply($products, $request);
        $subcategories = $this->subcategoryCounts($subcategories, $products, $categoryIds);
        $products = $products->latest('published_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'image' => $product->featured_image_path ? '/image/'.rawurlencode(basename($product->featured_image_path)) : null,
                'brand' => $product->brand?->name,
                'price' => $product->current_price,
                'regularPrice' => (float) $product->regular_price,
                'discount' => $product->discount_percentage,
                'stockStatus' => $product->stock_status,
            ]);

        return Inertia::render('app/modules/inventories/categories/pages/Show', [
            'category' => $category,
            'products' => $products,
            'brands' => $brands,
            'selectedBrand' => $brandSlug,
            'filters' => $request->only(['brand', 'min_price', 'max_price', 'in_stock']),
            'priceBounds' => $priceBounds,
            'subcategories' => $subcategories,
        ]);
    }

    /** @param array<int, array{id: int, name: string, slug: string, depth: int, categoryIds: array<int, int>}> $subcategories */
    private function subcategoryCounts(array $subcategories, Builder $products, Collection $categoryIds): array
    {
        if ($subcategories === []) {
            return [];
        }

        $eligibleProducts = (clone $products)->reorder()->select('products.id')->toBase();
        $countsQuery = DB::table('category_product as membership')
            ->joinSub($eligibleProducts, 'eligible', fn ($join) => $join->on('eligible.id', '=', 'membership.product_id'))
            ->whereIn('membership.category_id', $categoryIds);

        foreach ($subcategories as $index => $subcategory) {
            $placeholders = implode(', ', array_fill(0, count($subcategory['categoryIds']), '?'));
            $countsQuery->selectRaw(
                "COUNT(DISTINCT CASE WHEN membership.category_id IN ({$placeholders}) THEN membership.product_id END) AS count_{$index}",
                $subcategory['categoryIds']
            );
        }

        $counts = $countsQuery->first();

        return collect($subcategories)->map(fn (array $subcategory, int $index): array => [
            'id' => $subcategory['id'],
            'name' => $subcategory['name'],
            'slug' => $subcategory['slug'],
            'depth' => $subcategory['depth'],
            'productCount' => (int) ($counts->{'count_'.$index} ?? 0),
        ])->all();
    }

    public function create(StoreCategoryRequest $request): Category
    {
        $imagePath = $request->validated('image_path') ?: $this->storeCategoryImage->execute($request->file('image'));

        $shortDescription = $this->richTextSanitizer->sanitize($request->validated('short_description'));
        $description = $this->richTextSanitizer->sanitize($request->validated('description'));

        return $this->createCategory->execute(CategoryData::fromRequest($request, $shortDescription, $description), $imagePath);
    }

    public function update(StoreCategoryRequest $request, Category $category): Category
    {
        $imagePath = $category->image_path;

        if ($request->filled('image_path')) {
            $imagePath = $request->validated('image_path');
        } elseif ($request->hasFile('image')) {
            $newImagePath = $this->storeCategoryImage->execute($request->file('image'));
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $newImagePath;
        }

        $shortDescription = $this->richTextSanitizer->sanitize($request->validated('short_description'));
        $description = $this->richTextSanitizer->sanitize($request->validated('description'));
        $category->update([
            ...CategoryData::fromRequest($request, $shortDescription, $description)->toArray(),
            'image_path' => $imagePath,
        ]);

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages(['category' => 'Move or delete the child categories first.']);
        }

        $category->delete();
        if ($category->image_path !== null) {
            Storage::disk('public')->delete($category->image_path);
        }
    }

    private function paginate(?string $search): LengthAwarePaginator
    {
        $categories = Category::query()
            ->with('parent:id,name')
            ->withCount(['children', 'products'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $rows = collect();
        $visited = [];

        foreach ($categories->whereNull('parent_id') as $category) {
            $this->appendCategoryBranch($category, $categories, $rows, $visited);
        }

        foreach ($categories as $category) {
            if (! isset($visited[$category->id])) {
                $this->appendCategoryBranch($category, $categories, $rows, $visited);
            }
        }

        if ($search !== null && $search !== '') {
            $rows = $rows->filter(fn (Category $category): bool => str_contains(mb_strtolower($category->name), mb_strtolower($search))
                || str_contains(mb_strtolower($category->slug), mb_strtolower($search)))->values();
        }

        $page = CollectionPaginator::resolveCurrentPage();

        return (new CollectionPaginator(
            $rows->forPage($page, 10)->values(),
            $rows->count(),
            10,
            $page,
            ['path' => CollectionPaginator::resolveCurrentPath()],
        ))->withQueryString();
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, Category>  $rows
     * @param  array<int, true>  $visited
     */
    private function appendCategoryBranch(Category $category, Collection $categories, Collection $rows, array &$visited, int $depth = 0): void
    {
        if (isset($visited[$category->id])) {
            return;
        }

        $visited[$category->id] = true;
        $category->setAttribute('depth', $depth);
        $rows->push($category);

        foreach ($categories->where('parent_id', $category->id) as $child) {
            $this->appendCategoryBranch($child, $categories, $rows, $visited, $depth + 1);
        }
    }

    /** @return Collection<int, array{id: int, name: string, depth: int}> */
    private function parentOptions(): Collection
    {
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'parent_id', 'name']);

        return $this->flattenTree($categories);
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, array{id: int, name: string, depth: int}>
     */
    private function flattenTree(Collection $categories, ?int $parentId = null, int $depth = 0): Collection
    {
        return $categories
            ->where('parent_id', $parentId)
            ->flatMap(fn (Category $category): Collection => collect([[
                'id' => $category->id,
                'name' => $category->name,
                'depth' => $depth,
            ]])->concat($this->flattenTree($categories, $category->id, $depth + 1)))
            ->values();
    }
}
