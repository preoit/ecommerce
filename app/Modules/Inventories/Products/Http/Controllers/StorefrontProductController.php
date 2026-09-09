<?php

namespace App\Modules\Inventories\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Inventories\Products\Models\ProductQuestion;
use App\Modules\Inventories\Products\Models\ProductReview;
use App\Modules\Inventories\Products\Models\ProductVariant;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Categories\Services\CategoryService;
use App\Modules\Settings\Models\WebsiteSetting;
use App\Modules\Orders\Services\DeliveryChargeCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->with(['brand:id,name,slug','categories:id,name,slug'])
            ->whereIn('status',['Published','Active'])
            ->where('visibility','Public')
            ->when($request->string('search')->trim()->toString(), fn($query,$search)=>$query->where(fn($query)=>$query->where('title','like',"%{$search}%")->orWhere('sku','like',"%{$search}%")))
            ->when($request->string('category')->trim()->toString(), fn($query,$slug)=>$query->whereHas('categories',fn($query)=>$query->where('slug',$slug)))
            ->when($request->string('brand')->trim()->toString(), fn($query,$slug)=>$query->whereHas('brand',fn($query)=>$query->where('slug',$slug)))
            ->latest('published_at')->latest('id')->paginate(16)->withQueryString();
        $products->through(fn(Product $product)=>$this->cardData($product));

        $categorySlug = $request->string('category')->trim()->toString();
        $brandSlug = $request->string('brand')->trim()->toString();
        $listingSeo = $brandSlug
            ? \App\Modules\Inventories\Brands\Models\Brand::query()->where('slug', $brandSlug)->first(['seo_title', 'meta_description', 'meta_robots', 'canonical_url', 'og_title', 'og_description'])
            : ($categorySlug ? \App\Modules\Inventories\Categories\Models\Category::query()->where('slug', $categorySlug)->first(['seo_title', 'meta_description', 'meta_robots', 'canonical_url', 'og_title', 'og_description']) : null);
        return Inertia::render('app/modules/storefront/products/pages/Index', [
            'products'=>$products,
            'filters'=>$request->only(['search','category','brand']),
            'listingSeo'=>$listingSeo,
            'categories'=>\App\Modules\Inventories\Categories\Models\Category::where('is_active',true)->orderBy('name')->get(['id','name','slug']),
            'brands'=>\App\Modules\Inventories\Brands\Models\Brand::where('is_active',true)->orderBy('name')->get(['id','name','slug']),
        ]);
    }

    public function show(Request $request, string $slug): Response|RedirectResponse
    {
        $product = Product::with(['brand:id,name,slug','unit:id,name','categories:id,parent_id,name,slug','images','specifications','approvedReviews.images','questions' => fn ($q) => $q->where('status','approved')->latest(),'faqs','bulkPrices','variants' => fn ($query) => $query->where('is_active', true)])
            ->where('slug', $slug)->whereIn('status', ['Published','Active'])->where('visibility', 'Public')->first();
        if (!$product) {
            $redirect = DB::table('product_url_redirects')->where('old_slug', $slug)->first();
            if ($redirect && ($target = Product::find($redirect->product_id))) return redirect()->route('storefront.products.show', $target->slug, 301);

            $category = Category::query()->where('slug', $slug)->where('is_active', true)->first();
            if ($category) return app(CategoryService::class)->publicPage($request, $category);

            abort(404);
        }

        DB::table('recently_viewed_products')->updateOrInsert(
            ['product_id'=>$product->id, 'user_id'=>$request->user()?->id, 'session_id'=>$request->user() ? null : $request->session()->getId()],
            ['viewed_at'=>now()]
        );
        $reviews = $product->approvedReviews;
        $breakdown = collect(range(1,5))->mapWithKeys(fn ($star) => [$star => $reviews->where('rating',$star)->count()]);
        $categoryIds = $product->categories->pluck('id');
        $related = Product::with('brand:id,name')->whereKeyNot($product->id)->whereIn('status',['Published','Active'])->where('visibility','Public')
            ->where(fn ($q) => $q->where('brand_id',$product->brand_id)->orWhereHas('categories', fn ($q) => $q->whereIn('categories.id',$categoryIds)))
            ->limit(8)->get();
        $recentIds = DB::table('recently_viewed_products')->where($request->user() ? 'user_id' : 'session_id', $request->user()?->id ?? $request->session()->getId())->where('product_id','!=',$product->id)->latest('viewed_at')->limit(8)->pluck('product_id');

        return Inertia::render('app/modules/storefront/products/pages/Show', [
            'product' => $this->productData($product),
            'reviews' => $reviews->map(fn ($review) => ['id'=>$review->id,'rating'=>$review->rating,'title'=>$review->title,'description'=>$review->description,'customerName'=>$review->customer_name,'verified'=>$review->verified_purchase,'helpful'=>$review->helpful_count,'adminReply'=>$review->admin_reply,'date'=>$review->created_at->format('M j, Y'),'images'=>$review->images->map(fn ($image) => '/image/'.rawurlencode(basename($image->path)))]),
            'rating' => ['average'=>round((float)$reviews->avg('rating'),1),'total'=>$reviews->count(),'breakdown'=>$breakdown],
            'questions' => $product->questions,
            'related' => $related->map(fn ($item) => $this->cardData($item)),
            'recentlyViewed' => Product::whereIn('id',$recentIds)->get()->map(fn ($item) => $this->cardData($item)),
            'inWishlist' => $request->user() ? DB::table('wishlists')->where(['user_id'=>$request->user()->id,'product_id'=>$product->id])->exists() : false,
            'stockSettings' => $this->stockSettings(),
        ]);
    }

    public function legacyShow(string $slug): RedirectResponse
    {
        $product = Product::query()->where('slug', $slug)->first();
        if (!$product) {
            $redirect = DB::table('product_url_redirects')->where('old_slug', $slug)->first();
            $product = $redirect ? Product::find($redirect->product_id) : null;
        }

        abort_unless($product && in_array($product->status, ['Published', 'Active']) && $product->visibility === 'Public', 404);

        return redirect()->route('storefront.products.show', $product->slug, 301);
    }

    public function cart(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1'], 'variant_id' => ['nullable', 'integer']]);
        abort_unless(in_array($product->status, ['Published', 'Active']) && $product->visibility === 'Public', 404);
        $hasVariants = $product->variants()->where('is_active', true)->exists();
        $variant = filled($data['variant_id'] ?? null) ? $product->variants()->where('is_active', true)->find($data['variant_id']) : null;
        if ($hasVariants && !$variant) return response()->json(['message' => 'Please select a product option.'], 422);

        $settings = $this->stockSettings();
        $stock = $variant?->stock_quantity ?? $product->stock_quantity;
        $max = $settings['allowOutOfStockOrders'] ? ($product->max_order_quantity ?: PHP_INT_MAX) : min($stock, $product->max_order_quantity ?: $stock);
        if ($data['quantity'] < $product->min_order_quantity || $data['quantity'] > $max || (($data['quantity'] - $product->min_order_quantity) % max(1, $product->quantity_step)) !== 0) return response()->json(['message' => 'Selected quantity is not available.'], 422);
        $unitPrice = $variant ? (float) $variant->current_price : (float) ($product->bulkPrices()->where('min_quantity', '<=', $data['quantity'])->where(fn ($q) => $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $data['quantity']))->orderByDesc('min_quantity')->value('unit_price') ?? $product->current_price);
        $cart = $request->session()->get('cart', []);
        $cartKey = $variant ? "{$product->id}:{$variant->id}" : (string) $product->id;
        $cart[$cartKey] = ['product_id' => $product->id, 'variant_id' => $variant?->id, 'quantity' => $data['quantity'], 'unit_price' => $unitPrice, 'title' => $product->title, 'variant_name' => $variant?->name, 'slug' => $product->slug];
        $request->session()->put('cart', $cart);
        return response()->json(['message' => 'Product added to cart.', 'count' => collect($cart)->sum('quantity'), 'item' => $cart[$cartKey]]);
    }

    public function cartPage(Request $request): Response
    {
        return Inertia::render('app/modules/storefront/cart/pages/Index', $this->cartSummary($request));
    }

    public function updateCart(Request $request, string $cartKey): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $cart = $request->session()->get('cart', []);
        abort_unless(isset($cart[$cartKey]), 404);
        $line = $cart[$cartKey];
        $product = Product::findOrFail($line['product_id']);
        $variant = filled($line['variant_id'] ?? null) ? $product->variants()->where('is_active', true)->find($line['variant_id']) : null;
        if (filled($line['variant_id'] ?? null) && !$variant) return response()->json(['message' => 'This product option is no longer available.'], 422);
        $settings = $this->stockSettings();
        $stock = $variant?->stock_quantity ?? $product->stock_quantity;
        $max = $settings['allowOutOfStockOrders'] ? ($product->max_order_quantity ?: PHP_INT_MAX) : min($stock, $product->max_order_quantity ?: $stock);
        if ($data['quantity'] < $product->min_order_quantity || $data['quantity'] > $max || (($data['quantity'] - $product->min_order_quantity) % max(1, $product->quantity_step)) !== 0) return response()->json(['message' => 'Selected quantity is not available.'], 422);
        $cart[$cartKey]['quantity'] = $data['quantity'];
        $cart[$cartKey]['unit_price'] = $variant ? (float) $variant->current_price : (float) $product->current_price;
        $request->session()->put('cart', $cart);
        return response()->json(['message' => 'Cart updated.', ...$this->cartSummary($request)]);
    }

    public function removeCart(Request $request, string $cartKey): JsonResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$cartKey]);
        $request->session()->put('cart', $cart);
        return response()->json(['message' => 'Item removed from cart.', ...$this->cartSummary($request)]);
    }

    public function checkout(Request $request): Response
    {
        $summary = $this->cartSummary($request);
        if (empty($summary['items'])) {
            return redirect()->route('storefront.cart')->with('success', 'Your cart is empty.');
        }

        return Inertia::render('app/modules/storefront/checkout/pages/Index', $summary);
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:120'],
            'delivery_zone' => ['required', Rule::in(['inside_dhaka', 'outside_dhaka'])],
            'note' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::in(['cod'])],
        ]);
        $cart = $request->session()->get('cart', []);
        if (!$cart) return redirect()->route('storefront.cart')->with('success', 'Your cart is empty.');

        $orderNumber = null;
        $orderId = null;
        DB::transaction(function () use ($cart, $data, $request, &$orderNumber, &$orderId): void {
            $products = Product::query()->whereIn('id', collect($cart)->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
            $variants = ProductVariant::query()->whereIn('id', collect($cart)->pluck('variant_id')->filter())->lockForUpdate()->get()->keyBy('id');
            $settings = $this->stockSettings();
            $subtotal = 0;
            $deliveryLines = collect();
            $hasStockShortage = false;

            foreach ($cart as $key => $line) {
                $product = $products->get($line['product_id']);
                $variant = filled($line['variant_id'] ?? null) ? $variants->get($line['variant_id']) : null;
                if (!$product || !in_array($product->status, ['Published', 'Active']) || (filled($line['variant_id'] ?? null) && (!$variant || !$variant->is_active))) abort(422, 'One or more items are no longer available.');
                $stock = $variant?->stock_quantity ?? $product->stock_quantity;
                if (!$settings['allowOutOfStockOrders'] && $stock < $line['quantity']) abort(422, 'One or more items are no longer available.');
                $price = $variant ? (float) $variant->current_price : (float) ($product->bulkPrices()->where('min_quantity', '<=', $line['quantity'])->where(fn ($query) => $query->whereNull('max_quantity')->orWhere('max_quantity', '>=', $line['quantity']))->orderByDesc('min_quantity')->value('unit_price') ?? $product->current_price);
                $cart[$key]['unit_price'] = $price;
                $hasStockShortage = $hasStockShortage || $stock < $line['quantity'];
                $subtotal += $line['quantity'] * $price;
                $deliveryLines->push(['product' => $product, 'quantity' => $line['quantity']]);
            }

            $delivery = app(DeliveryChargeCalculator::class)->calculate(WebsiteSetting::firstOrCreate(['id' => 1]), $deliveryLines, $subtotal, $data['delivery_zone'], $data['payment_method']);

            $orderNumber = '#ORD'.str_pad((string) ((int) DB::table('orders')->max('id') + 1), 2, '0', STR_PAD_LEFT);
            $orderId = DB::table('orders')->insertGetId([
                'order_number' => $orderNumber, 'user_id' => $request->user()?->id,
                'customer_name' => $data['customer_name'], 'phone' => $data['phone'], 'email' => $data['email'] ?? null,
                'address' => $data['address'], 'city' => $data['city'], 'delivery_zone' => $data['delivery_zone'], 'note' => $data['note'] ?? null,
                'payment_method' => $data['payment_method'], 'payment_status' => 'pending', 'status' => 'pending',
                'subtotal' => $subtotal, 'shipping_total' => $delivery['shipping'], 'cod_surcharge' => $delivery['cod'], 'delivery_breakdown' => json_encode($delivery), 'total' => $subtotal + $delivery['total'], 'has_stock_shortage' => $hasStockShortage,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($cart as $line) {
                $product = $products->get($line['product_id']);
                $variant = filled($line['variant_id'] ?? null) ? $variants->get($line['variant_id']) : null;
                $stock = $variant?->stock_quantity ?? $product->stock_quantity;
                $shortage = max(0, $line['quantity'] - $stock);
                DB::table('order_items')->insert(['order_id' => $orderId, 'product_id' => $product->id, 'product_variant_id' => $variant?->id, 'product_title' => $product->title, 'variant_name' => $variant?->name, 'sku' => $variant?->sku ?: $product->sku, 'unit_price' => $line['unit_price'], 'quantity' => $line['quantity'], 'line_total' => $line['quantity'] * $line['unit_price'], 'stock_shortage_quantity' => $shortage, 'created_at' => now(), 'updated_at' => now()]);
                if ($variant && $variant->stock_quantity > 0) $variant->decrement('stock_quantity', min($variant->stock_quantity, $line['quantity']));
                elseif (!$variant && $product->stock_quantity > 0) $product->decrement('stock_quantity', min($product->stock_quantity, $line['quantity']));
            }
        });
        $request->session()->forget('cart');

        return redirect()->route('storefront.order.success', $orderId);
    }

    public function orderSuccess(Request $request, int $order): Response
    {
        $order = DB::table('orders')->find($order);
        abort_unless($order, 404);

        return Inertia::render('app/modules/storefront/checkout/pages/Success', ['order' => $order]);
    }

    public function wishlist(Request $request, Product $product): JsonResponse
    {
        $existing = DB::table('wishlists')->where(['user_id'=>$request->user()->id,'product_id'=>$product->id]);
        if ($existing->exists()) { $existing->delete(); $active=false; } else { DB::table('wishlists')->insert(['user_id'=>$request->user()->id,'product_id'=>$product->id,'created_at'=>now(),'updated_at'=>now()]); $active=true; }
        return response()->json(['active'=>$active]);
    }

    public function wishlistPage(Request $request): Response
    {
        $ids=DB::table('wishlists')->where('user_id',$request->user()->id)->latest()->pluck('product_id');
        return Inertia::render('app/modules/storefront/products/pages/ProductList',['title'=>'My Wishlist','products'=>Product::with('brand:id,name')->whereIn('id',$ids)->get()->map(fn($item)=>$this->cardData($item))]);
    }

    public function comparePage(Request $request): Response
    {
        $query=DB::table('compare_products')->where($request->user()?'user_id':'session_id',$request->user()?->id??$request->session()->getId())->latest()->limit(4);
        $products=Product::with(['brand:id,name','specifications'])->whereIn('id',$query->pluck('product_id'))->get();
        return Inertia::render('app/modules/storefront/products/pages/ProductList',['title'=>'Compare Products','compare'=>true,'products'=>$products->map(fn($item)=>[...$this->cardData($item),'specifications'=>$item->specifications,'warranty'=>$item->warranty,'stock'=>$item->stock_quantity])]);
    }

    public function helpful(Request $request, ProductReview $review): JsonResponse
    {
        abort_unless($review->status==='approved',404);
        $key=['review_id'=>$review->id,'user_id'=>$request->user()?->id,'session_id'=>$request->user() ? null : $request->session()->getId()];
        if(DB::table('product_review_votes')->where($key)->exists()) return response()->json(['message'=>'You already marked this review helpful.','helpful'=>$review->helpful_count]);
        DB::transaction(function()use($key,$review){DB::table('product_review_votes')->insert([...$key,'created_at'=>now(),'updated_at'=>now()]);$review->increment('helpful_count');});
        return response()->json(['message'=>'Thanks for your feedback.','helpful'=>$review->fresh()->helpful_count]);
    }

    public function compare(Request $request, Product $product): JsonResponse
    {
        $key = ['product_id'=>$product->id,'user_id'=>$request->user()?->id,'session_id'=>$request->user() ? null : $request->session()->getId()];
        $query=DB::table('compare_products')->where($key); if($query->exists()){$query->delete();$active=false;}else{DB::table('compare_products')->insert([...$key,'created_at'=>now(),'updated_at'=>now()]);$active=true;}
        return response()->json(['active'=>$active]);
    }

    public function notify(Request $request, Product $product): JsonResponse
    {
        $data=$request->validate(['email'=>['nullable','email','required_without:phone'],'phone'=>['nullable','string','max:20','required_without:email']]);
        DB::table('stock_notifications')->insert(['product_id'=>$product->id,'user_id'=>$request->user()?->id,'email'=>$data['email']??null,'phone'=>$data['phone']??null,'created_at'=>now(),'updated_at'=>now()]);
        return response()->json(['message'=>'We will notify you when this product is available.'],201);
    }

    public function review(Request $request, Product $product): JsonResponse
    {
        $data=$request->validate(['rating'=>['required','integer','between:1,5'],'title'=>['nullable','string','max:150'],'description'=>['required','string','max:5000'],'customer_name'=>['required','string','max:120'],'customer_email'=>['nullable','email'],'video_url'=>['nullable','url','max:255']]);
        $verified=false; // Order linkage is intentionally required before granting this badge.
        ProductReview::create([...$data,'product_id'=>$product->id,'user_id'=>$request->user()?->id,'verified_purchase'=>$verified,'status'=>'pending']);
        return response()->json(['message'=>'Your review was submitted for approval.'],201);
    }

    public function question(Request $request, Product $product): JsonResponse
    {
        $data=$request->validate(['customer_name'=>['required','string','max:120'],'customer_email'=>['nullable','email'],'question'=>['required','string','max:2000']]);
        ProductQuestion::create([...$data,'product_id'=>$product->id,'user_id'=>$request->user()?->id,'status'=>'pending']);
        return response()->json(['message'=>'Your question was submitted for approval.'],201);
    }

    private function cartSummary(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        $products = Product::query()->whereIn('id', collect($cart)->pluck('product_id'))->get()->keyBy('id');
        $variants = ProductVariant::query()->whereIn('id', collect($cart)->pluck('variant_id')->filter())->get()->keyBy('id');
        $settings = $this->stockSettings();
        $items = collect($cart)->map(function (array $line, string|int $cartKey) use ($products, $variants, $settings): ?array {
            $product = $products->get($line['product_id']);
            $variant = filled($line['variant_id'] ?? null) ? $variants->get($line['variant_id']) : null;
            if (!$product || (filled($line['variant_id'] ?? null) && !$variant)) return null;
            $stock = $variant?->stock_quantity ?? $product->stock_quantity;
            $quantity = $settings['allowOutOfStockOrders'] ? (int) $line['quantity'] : min((int) $line['quantity'], max(0, (int) $stock));
            if (!$quantity) return null;
            $price = $variant ? (float) $variant->current_price : (float) $product->current_price;
            $imagePath = $variant?->image_path ?: $product->featured_image_path;
            return ['cart_key' => (string) $cartKey, 'product_id' => $product->id, 'variant_id' => $variant?->id, 'variant_name' => $variant?->name, 'title' => $product->title, 'slug' => $product->slug, 'quantity' => $quantity, 'unit_price' => $price, 'line_total' => $quantity * $price, 'stock_quantity' => $stock, 'min_quantity' => (int) ($product->min_order_quantity ?: 1), 'quantity_step' => (int) ($product->quantity_step ?: 1), 'max_quantity' => $settings['allowOutOfStockOrders'] ? ($product->max_order_quantity ?: null) : $stock, 'image' => $imagePath ? '/image/'.rawurlencode(basename($imagePath)) : null, 'weight' => (float) ($product->weight ?? 0), 'delivery_inside_dhaka' => $product->delivery_inside_dhaka !== null ? (float) $product->delivery_inside_dhaka : null, 'delivery_outside_dhaka' => $product->delivery_outside_dhaka !== null ? (float) $product->delivery_outside_dhaka : null];
        })->filter()->values();

        $delivery = WebsiteSetting::firstOrCreate(['id' => 1]);
        return ['items' => $items, 'subtotal' => $items->sum('line_total'), 'cartCount' => $items->sum('quantity'), 'deliverySettings' => [
            'enabled' => (bool) ($delivery->delivery_enabled ?? true), 'insideDhaka' => (float) ($delivery->delivery_inside_dhaka ?? 80), 'outsideDhaka' => (float) ($delivery->delivery_outside_dhaka ?? 150),
            'freeEnabled' => (bool) $delivery->free_delivery_enabled, 'freeThreshold' => $delivery->free_delivery_threshold !== null ? (float) $delivery->free_delivery_threshold : null,
            'codEnabled' => (bool) $delivery->cod_surcharge_enabled, 'codSurcharge' => (float) ($delivery->cod_surcharge ?? 0), 'productOverrideEnabled' => (bool) ($delivery->product_delivery_override_enabled ?? true),
            'heavyEnabled' => (bool) $delivery->heavy_delivery_enabled, 'heavyThreshold' => (float) ($delivery->heavy_weight_threshold ?? 5), 'heavyPerKg' => (float) ($delivery->heavy_charge_per_kg ?? 0),
        ]];
    }

    private function cardData(Product $item): array { return ['id'=>$item->id,'title'=>$item->title,'slug'=>$item->slug,'image'=>$item->featured_image_path?'/image/'.rawurlencode(basename($item->featured_image_path)):null,'price'=>$item->current_price,'regularPrice'=>(float)$item->regular_price,'discount'=>$item->discount_percentage,'stockStatus'=>$item->stock_status,'brand'=>$item->brand?->name]; }
    private function productData(Product $p): array
    {
        $groups = $p->specifications
            ->groupBy(fn ($specification) => $specification->group_title ?: 'General Specifications')
            ->map(fn ($items, $title) => ['title' => $title, 'items' => $items->values()])
            ->values();

        return [...$p->toArray(), 'variants' => $p->variants->map(fn ($variant) => [...$variant->toArray(), 'image_url' => $this->mediaUrl($variant->image_path)])->values(), 'category_breadcrumb' => $this->categoryBreadcrumb($p), 'specifications' => [], 'specification_groups' => $groups, 'featured_image_url'=>$this->mediaUrl($p->featured_image_path),'gallery_urls'=>collect($p->gallery??[])->filter()->map(fn($path)=>$this->mediaUrl($path))->values(),'og_image_url'=>$this->mediaUrl($p->og_image_path),'twitter_image_url'=>$this->mediaUrl($p->twitter_image_path)];
    }

    private function categoryBreadcrumb(Product $product): array
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->get(['id', 'parent_id', 'name', 'slug'])
            ->keyBy('id');
        $deepestPath = [];

        foreach ($product->categories as $assignedCategory) {
            $path = [];
            $visited = [];
            $category = $categories->get($assignedCategory->id) ?? $assignedCategory;

            while ($category && !isset($visited[$category->id])) {
                $visited[$category->id] = true;
                array_unshift($path, ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug]);
                $category = $category->parent_id ? $categories->get($category->parent_id) : null;
            }

            if (count($path) > count($deepestPath)) $deepestPath = $path;
        }

        return $deepestPath;
    }

    private function stockSettings(): array
    {
        $settings = WebsiteSetting::query()->find(1);

        return ['allowOutOfStockOrders' => (bool) ($settings?->allow_out_of_stock_orders ?? true), 'showStockToCustomers' => (bool) ($settings?->show_stock_to_customers ?? false)];
    }

    private function mediaUrl(?string $path): ?string
    {
        if (!$path) return null;
        $normalizedPath = ltrim(str_replace('\\', '/', preg_replace('#^/?storage/#', '', $path)), '/');
        $version = is_file(storage_path('app/public/'.$normalizedPath)) ? filemtime(storage_path('app/public/'.$normalizedPath)) : 1;
        $encodedPath = collect(explode('/', $normalizedPath))->map(fn ($segment) => rawurlencode($segment))->implode('/');

        return '/image/'.rawurlencode(basename($normalizedPath)).'?v='.$version;
    }
}
