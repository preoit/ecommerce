<?php

namespace App\Modules\Inventories\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventories\Brands\Models\Brand;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Inventories\Units\Models\Unit;
use App\Modules\Settings\Models\WebsiteMedia;
use App\Support\Content\RichTextSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CreateProductController extends Controller
{
    public function __construct(private readonly RichTextSanitizer $sanitizer) {}

    public function storeBrand(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'], 'short_description' => ['nullable', 'string', 'max:500'], 'description' => ['nullable', 'string'], 'image_path' => ['nullable', 'string', 'max:255']]);
        $baseSlug = Str::slug($data['slug'] ?? $data['name']) ?: 'brand';
        $slug = $baseSlug;
        $suffix = 2;
        while (Brand::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }
        $brand = Brand::create(['name' => $data['name'], 'slug' => $slug, 'short_description' => $data['short_description'] ?? null, 'description' => $data['description'] ?? null, 'image_path' => $data['image_path'] ?? null, 'is_active' => true]);

        return response()->json(['category' => ['id' => $brand->id, 'name' => $brand->name, 'slug' => $brand->slug]], 201);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'parent_id' => ['nullable', 'integer', 'exists:categories,id'], 'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'], 'short_description' => ['nullable', 'string', 'max:500'], 'description' => ['nullable', 'string'], 'image_path' => ['nullable', 'string', 'max:255']]);
        $baseSlug = Str::slug($data['slug'] ?? $data['name']) ?: 'category';
        $slug = $baseSlug;
        $suffix = 2;
        while (Category::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }
        $category = Category::create(['name' => $data['name'], 'parent_id' => $data['parent_id'] ?? null, 'slug' => $slug, 'short_description' => $data['short_description'] ?? null, 'description' => $data['description'] ?? null, 'image_path' => $data['image_path'] ?? null, 'is_active' => true]);

        return response()->json(['category' => ['id' => $category->id, 'name' => $category->name, 'parent_id' => $category->parent_id, 'children' => []]], 201);
    }

    public function index(): Response
    {
        return Inertia::render('app/modules/inventories/products/pages/Index', [
            'products' => Product::query()
                ->with(['brand:id,name', 'unit:id,name', 'categories:id,name', 'images'])
                ->latest()
                ->get()
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'image' => $product->featured_image_path ? '/storage/'.$product->featured_image_path : null,
                    'price' => $product->regular_price,
                    'salePrice' => $product->sale_price,
                    'sku' => $product->sku,
                    'stock' => $product->stock_quantity,
                    'status' => $product->status,
                    'visibility' => $product->visibility,
                    'brand' => $product->brand?->name,
                    'unit' => $product->unit?->name,
                    'categories' => $product->categories->pluck('name')->values(),
                    'description' => $product->description,
                    'shortDescription' => $product->short_description,
                    'barcode' => $product->barcode,
                    'model' => $product->model,
                    'manufacturer' => $product->manufacturer,
                    'countryOfOrigin' => $product->country_of_origin,
                    'weight' => $product->weight,
                    'dimensions' => trim(collect([$product->length, $product->width, $product->height])->filter(fn ($value) => $value !== null)->implode(' × ')),
                    'warranty' => $product->warranty,
                    'gallery' => $product->images->map(fn ($image) => '/storage/'.$image->path)->values(),
                    'deliveryInsideDhaka' => $product->delivery_inside_dhaka,
                    'deliveryOutsideDhaka' => $product->delivery_outside_dhaka,
                    'estimatedDelivery' => $product->estimated_delivery,
                    'returnPolicy' => $product->return_policy,
                    'replacementPolicy' => $product->replacement_policy,
                    'paymentMethods' => $product->payment_methods,
                    'seoTitle' => $product->seo_title,
                    'metaDescription' => $product->meta_description,
                ]),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $request->merge([
            'regular' => $request->input('regular', $request->input('regular_price')),
            'sale' => $request->input('sale', $request->input('sale_price')),
            'stock' => $request->input('stock', $request->input('stock_quantity')),
        ]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'regular' => ['required', 'numeric', 'min:0'],
            'sale' => ['nullable', 'numeric', 'min:0', 'lt:regular'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['Draft', 'Published', 'Active', 'Inactive', 'Discontinued'])],
            'visibility' => ['required', Rule::in(['Public', 'Private'])],
            'featuredImage' => ['nullable', 'array'],
            'featuredImage.path' => ['nullable', 'string', 'max:2048'],
            'gallery' => ['nullable', 'array'],
            'gallery.*.path' => ['required', 'string', 'max:2048'],
        ]);

        if ($data['status'] === 'Published' && !$product->published_at) {
            $data['published_at'] = now();
        }

        $updates = [
            'title' => $data['title'],
            'regular_price' => $data['regular'],
            'sale_price' => $data['sale'] ?? null,
            'stock_quantity' => $data['stock'],
            'status' => $data['status'],
            'visibility' => $data['visibility'],
            'published_at' => $data['published_at'] ?? $product->published_at,
        ];

        if (array_key_exists('featuredImage', $data)) {
            $updates['featured_image_path'] = $data['featuredImage']['path'] ?? null;
        }

        if (array_key_exists('gallery', $data)) {
            $updates['gallery'] = collect($data['gallery'] ?? [])->pluck('path')->filter()->values()->all();
        }

        $product->update($updates);

        return back()->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    public function __invoke(Request $request): Response
    {
        $categoryGroups = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name'])
            ->groupBy('parent_id');
        $categoryTree = function (?int $parentId = null) use (&$categoryTree, $categoryGroups): array {
            return $categoryGroups->get($parentId, collect())->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'children' => $categoryTree($category->id),
            ])->values()->all();
        };

        return Inertia::render('app/modules/inventories/products/pages/Create', [
            'categories' => $categoryTree(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->pluck('name'),
            'units' => Unit::where('is_active', true)->orderBy('name')->pluck('name'),
            'media' => WebsiteMedia::latest()->get()->map(fn (WebsiteMedia $file): array => [
                'id' => $file->id,
                'name' => $file->name,
                'url' => $file->publicUrl(),
                'path' => $file->path,
            ]),
            'editingProduct' => $request->integer('edit') ? Product::with(['categories', 'brand', 'unit', 'images'])->find($request->integer('edit'))?->toArray() : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'=>['required','string','max:255'],'slug'=>['nullable','string','max:180','unique:products,slug'],'shortDescription'=>['nullable','string','max:1000'],'description'=>['nullable','string'],'regular'=>['required','numeric','min:0'],'sale'=>['nullable','numeric','min:0','lt:regular'],'sku'=>['nullable','string','max:100','unique:products,sku'],'barcode'=>['nullable','string','max:100','unique:products,barcode'],'stock'=>['required','integer','min:0'],'lowStockThreshold'=>['integer','min:0'],'minOrder'=>['integer','min:1'],'maxOrder'=>['nullable','integer','gte:minOrder'],'quantityStep'=>['integer','min:1'],'unit'=>['nullable','string'],'brand'=>['nullable','string'],'category'=>['array'],'category.*'=>['integer','exists:categories,id'],'status'=>['required','in:Draft,Published,Active,Inactive,Discontinued'],'visibility'=>['required','in:Public,Private'],'featuredImage'=>['nullable','array'],'gallery'=>['array'],'videoUrl'=>['nullable','url','max:255'],'model'=>['nullable','string','max:150'],'manufacturer'=>['nullable','string','max:150'],'countryOfOrigin'=>['nullable','string','max:100'],'weight'=>['nullable','numeric','min:0'],'length'=>['nullable','numeric','min:0'],'width'=>['nullable','numeric','min:0'],'height'=>['nullable','numeric','min:0'],'warranty'=>['nullable','string','max:255'],'keyFeatures'=>['nullable','string'],'keyBenefits'=>['nullable','string'],'boxContents'=>['nullable','string'],'howToUse'=>['nullable','string'],'suitableFor'=>['nullable','string'],'careInstructions'=>['nullable','string'],'deliveryInsideDhaka'=>['nullable','numeric','min:0'],'deliveryOutsideDhaka'=>['nullable','numeric','min:0'],'estimatedDelivery'=>['nullable','string','max:100'],'cashOnDelivery'=>['boolean'],'advancePayment'=>['nullable','string','max:1000'],'returnPolicy'=>['nullable','string','max:2000'],'replacementPolicy'=>['nullable','string','max:2000'],'paymentMethods'=>['nullable','string','max:500'],'isFeatured'=>['boolean'],'isNewArrival'=>['boolean'],'isBestSeller'=>['boolean'],'specifications'=>['array'],'specifications.*.group_title'=>['nullable','string','max:150'],'specifications.*.name'=>['required_with:specifications.*.value','string','max:150'],'specifications.*.value'=>['required_with:specifications.*.name','string','max:1000'],'seoTitle'=>['nullable','string','max:160'],'meta'=>['nullable','string','max:320'],'canonicalUrl'=>['nullable','url','max:255'],'metaRobots'=>['required',Rule::in(['index,follow','noindex,follow','noindex,nofollow'])],'focusKeyword'=>['nullable','string','max:255'],'ogTitle'=>['nullable','string','max:160'],'ogDescription'=>['nullable','string','max:320'],'ogImage'=>['nullable','array'],'twitterImage'=>['nullable','array'],'tags'=>['nullable','string','max:1000'],
        ]);
        foreach (['description','keyFeatures','keyBenefits','boxContents','howToUse','suitableFor','careInstructions'] as $richTextField) {
            $data[$richTextField] = $this->sanitizer->sanitize($data[$richTextField] ?? null);
        }
        $product = Product::create([
            'title'=>$data['title'],'slug'=>$data['slug'] ?: Str::slug($data['title']).'-'.Str::lower(Str::random(5)),'short_description'=>$data['shortDescription']??null,'description'=>$data['description']??null,'regular_price'=>$data['regular'],'sale_price'=>$data['sale']??null,'sku'=>$data['sku']??null,'barcode'=>$data['barcode']??null,'stock_quantity'=>$data['stock'],'low_stock_threshold'=>$data['lowStockThreshold'],'min_order_quantity'=>$data['minOrder'],'max_order_quantity'=>$data['maxOrder']??null,'quantity_step'=>$data['quantityStep'],'unit_id'=>Unit::where('name',$data['unit']??null)->value('id'),'brand_id'=>Brand::where('name',$data['brand']??null)->value('id'),'status'=>$data['status'],'visibility'=>$data['visibility'],'featured_image_path'=>$data['featuredImage']['path']??null,'gallery'=>collect($data['gallery']??[])->pluck('path')->all(),'video_url'=>$data['videoUrl']??null,'model'=>$data['model']??null,'manufacturer'=>$data['manufacturer']??null,'country_of_origin'=>$data['countryOfOrigin']??null,'weight'=>$data['weight']??null,'length'=>$data['length']??null,'width'=>$data['width']??null,'height'=>$data['height']??null,'warranty'=>$data['warranty']??null,'key_features'=>$data['keyFeatures']??null,'key_benefits'=>$data['keyBenefits']??null,'box_contents'=>$data['boxContents']??null,'how_to_use'=>$data['howToUse']??null,'suitable_for'=>$data['suitableFor']??null,'care_instructions'=>$data['careInstructions']??null,'delivery_inside_dhaka'=>$data['deliveryInsideDhaka']??null,'delivery_outside_dhaka'=>$data['deliveryOutsideDhaka']??null,'estimated_delivery'=>$data['estimatedDelivery']??null,'cash_on_delivery'=>$data['cashOnDelivery'],'advance_payment'=>$data['advancePayment']??null,'return_policy'=>$data['returnPolicy']??null,'replacement_policy'=>$data['replacementPolicy']??null,'payment_methods'=>$data['paymentMethods']??null,'is_featured'=>$data['isFeatured'],'is_new_arrival'=>$data['isNewArrival'],'is_best_seller'=>$data['isBestSeller'],'seo_title'=>$data['seoTitle']??null,'meta_description'=>$data['meta']??null,'canonical_url'=>$data['canonicalUrl']??null,'meta_robots'=>$data['metaRobots'],'focus_keyword'=>$data['focusKeyword']??null,'og_title'=>$data['ogTitle']??null,'og_description'=>$data['ogDescription']??null,'og_image_path'=>$data['ogImage']['path']??null,'twitter_image_path'=>$data['twitterImage']['path']??null,'tags'=>$data['tags']??null,'published_at'=>$data['status']==='Published'?now():null,
        ]);
        $product->specifications()->createMany(collect($data['specifications']??[])->filter(fn($item)=>filled($item['name']??null)&&filled($item['value']??null))->values()->map(fn($item,$index)=>[...$item,'sort_order'=>$index])->all());
        $product->categories()->sync($data['category'] ?? []);
        return to_route('inventories.products.index')->with('success', 'Product published successfully.');
    }
}
