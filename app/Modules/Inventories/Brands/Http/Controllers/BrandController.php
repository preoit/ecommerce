<?php

namespace App\Modules\Inventories\Brands\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventories\Brands\Models\Brand;
use App\Support\Content\RichTextSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $brands = Brand::query()->with('parent:id,name')->withCount('children')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"))
            ->orderBy('sort_order')->orderBy('name')->paginate(10)->withQueryString();
        $brands->getCollection()->each(fn (Brand $brand) => $brand->setAttribute('products_count', 0)->setAttribute('depth', 0));
        return Inertia::render('app/modules/inventories/categories/pages/Index', [
            'categories' => $brands,
            'parentOptions' => Brand::orderBy('name')->get(['id', 'name'])->map(fn (Brand $brand) => ['id' => $brand->id, 'name' => $brand->name, 'depth' => 0]),
            'filters' => ['search' => $search], 'resource' => 'brands', 'entityLabel' => 'brand',
        ]);
    }

    public function store(Request $request): RedirectResponse { return $this->save($request); }
    public function update(Request $request, Brand $brand): RedirectResponse { return $this->save($request, $brand); }
    public function destroy(Brand $brand): RedirectResponse { if ($brand->children()->exists()) return back()->withErrors(['brand' => 'Move or delete child brands first.']); Storage::disk('public')->delete($brand->image_path); $brand->delete(); return to_route('inventories.brands.index')->with('success', 'Brand deleted successfully.'); }

    private function save(Request $request, ?Brand $brand = null): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'parent_id' => ['nullable', 'exists:brands,id'], 'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:brands,slug,'.($brand?->id ?? 'NULL')], 'short_description' => ['nullable', 'string', 'max:5000'], 'description' => ['nullable', 'string'], 'image_path' => ['nullable', 'string', 'max:255']]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['short_description'] = $this->sanitizer->sanitize($data['short_description'] ?? null);
        $data['description'] = $this->sanitizer->sanitize($data['description'] ?? null);
        if ($brand) { $brand->update($data); $message = 'Brand updated successfully.'; } else { Brand::create($data); $message = 'Brand created successfully.'; }
        return to_route('inventories.brands.index')->with('success', $message);
    }
}
