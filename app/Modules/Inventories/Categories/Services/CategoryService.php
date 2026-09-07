<?php

namespace App\Modules\Inventories\Categories\Services;

use App\Modules\Inventories\Categories\Actions\CreateCategoryAction;
use App\Modules\Inventories\Categories\Actions\StoreCategoryImageAction;
use App\Modules\Inventories\Categories\DTOs\CategoryData;
use App\Modules\Inventories\Categories\Http\Requests\StoreCategoryRequest;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use App\Support\Content\RichTextSanitizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as CollectionPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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

    public function publicPage(Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $category->load([
            'parent:id,name,slug',
            'children' => fn ($query) => $query->where('is_active', true),
        ]);

        $allCategories = Category::query()->where('is_active', true)->get(['id', 'parent_id']);
        $categoryIds = collect([$category->id]);
        $appendChildren = function (int $parentId) use (&$appendChildren, $allCategories, $categoryIds): void {
            foreach ($allCategories->where('parent_id', $parentId) as $child) {
                $categoryIds->push($child->id);
                $appendChildren($child->id);
            }
        };
        $appendChildren($category->id);

        $products = Product::query()
            ->with(['brand:id,name', 'categories:id,name'])
            ->whereIn('status', ['Published', 'Active'])
            ->where('visibility', 'Public')
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds->unique()))
            ->latest('published_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'image' => $product->featured_image_path ? '/storage/'.$product->featured_image_path : null,
                'brand' => $product->brand?->name,
                'price' => $product->current_price,
                'regularPrice' => (float) $product->regular_price,
                'discount' => $product->discount_percentage,
                'stockStatus' => $product->stock_status,
            ]);

        return Inertia::render('app/modules/inventories/categories/pages/Show', [
            'category' => $category,
            'products' => $products,
        ]);
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
