<?php

namespace App\Modules\Inventories\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Blog\BlogController;
use App\Modules\Blog\Models\BlogCategory;
use App\Modules\Inventories\Brands\Models\Brand;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Inventories\Categories\Services\CategoryService;
use App\Modules\Inventories\Products\Models\Product;
use App\Modules\Inventories\Products\Models\ProductQuestion;
use App\Modules\Inventories\Products\Models\ProductReview;
use App\Modules\Inventories\Products\Models\ProductVariant;
use App\Modules\Orders\Services\CartPricingService;
use App\Modules\Orders\Services\DeliveryChargeCalculator;
use App\Modules\Orders\Services\DeliveryZoneDetector;
use App\Modules\Settings\Models\WebsiteSetting;
use App\Services\CheckoutPhoneVerificationService;
use App\Support\ProductListingFilters;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontProductController extends Controller
{
    public function __construct(private readonly CartPricingService $cartPricing) {}

    public function index(Request $request): Response
    {
        $products = Product::query()
            ->with(['brand:id,name,slug', 'categories:id,name,slug'])
            ->whereIn('status', ['Published', 'Active'])
            ->where('visibility', 'Public')
            ->when($request->string('search')->trim()->toString(), fn ($query, $search) => $query->where(fn ($query) => $query->where('title', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($request->string('category')->trim()->toString(), fn ($query, $slug) => $query->whereHas('categories', fn ($query) => $query->where('slug', $slug)))
            ->when($request->string('brand')->trim()->toString(), fn ($query, $slug) => $query->whereHas('brand', fn ($query) => $query->where('slug', $slug)));
        $priceBounds = ProductListingFilters::apply($products, $request);
        $products = $products->latest('published_at')->latest('id')->paginate(16)->withQueryString();
        $products->through(fn (Product $product) => $this->cardData($product));

        $categorySlug = $request->string('category')->trim()->toString();
        $brandSlug = $request->string('brand')->trim()->toString();
        $listingSeo = $brandSlug
            ? Brand::query()->where('slug', $brandSlug)->first(['seo_title', 'meta_description', 'meta_robots', 'canonical_url', 'og_title', 'og_description'])
            : ($categorySlug ? Category::query()->where('slug', $categorySlug)->first(['seo_title', 'meta_description', 'meta_robots', 'canonical_url', 'og_title', 'og_description']) : null);

        return Inertia::render('app/modules/storefront/products/pages/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'category', 'brand', 'min_price', 'max_price', 'in_stock']),
            'priceBounds' => $priceBounds,
            'listingSeo' => $listingSeo,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function show(Request $request, string $slug): Response|RedirectResponse
    {
        $product = Product::with(['brand:id,name,slug', 'unit:id,name', 'categories:id,parent_id,name,slug', 'images', 'specifications', 'approvedReviews.images', 'questions' => fn ($q) => $q->where('status', 'approved')->latest(), 'faqs', 'bulkPrices', 'variants' => fn ($query) => $query->where('is_active', true)])
            ->where('slug', $slug)->whereIn('status', ['Published', 'Active'])->where('visibility', 'Public')->first();
        if (! $product) {
            $redirect = DB::table('product_url_redirects')->where('old_slug', $slug)->first();
            if ($redirect && ($target = Product::find($redirect->product_id))) {
                return redirect()->route('storefront.products.show', $target->slug, 301);
            }

            $category = Category::query()->where('slug', $slug)->where('is_active', true)->first();
            if ($category) {
                return app(CategoryService::class)->publicPage($request, $category);
            }

            if (BlogCategory::query()->where('slug', $slug)->exists()) {
                return app(BlogController::class)->listing($request, $slug);
            }

            abort(404);
        }

        DB::table('recently_viewed_products')->updateOrInsert(
            ['product_id' => $product->id, 'user_id' => $request->user()?->id, 'session_id' => $request->user() ? null : $request->session()->getId()],
            ['viewed_at' => now()]
        );
        $reviews = $product->approvedReviews;
        $breakdown = collect(range(1, 5))->mapWithKeys(fn ($star) => [$star => $reviews->where('rating', $star)->count()]);
        $categoryIds = $product->categories->pluck('id');
        $related = Product::with('brand:id,name')->whereKeyNot($product->id)->whereIn('status', ['Published', 'Active'])->where('visibility', 'Public')
            ->where(fn ($q) => $q->where('brand_id', $product->brand_id)->orWhereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds)))
            ->limit(8)->get();
        $recentIds = DB::table('recently_viewed_products')->where($request->user() ? 'user_id' : 'session_id', $request->user()?->id ?? $request->session()->getId())->where('product_id', '!=', $product->id)->latest('viewed_at')->limit(8)->pluck('product_id');

        return Inertia::render('app/modules/storefront/products/pages/Show', [
            'product' => $this->productData($product),
            'reviews' => $reviews->map(fn ($review) => ['id' => $review->id, 'rating' => $review->rating, 'title' => $review->title, 'description' => $review->description, 'customerName' => $review->customer_name, 'verified' => $review->verified_purchase, 'helpful' => $review->helpful_count, 'adminReply' => $review->admin_reply, 'date' => $review->created_at->format('M j, Y'), 'images' => $review->images->map(fn ($image) => '/image/'.rawurlencode(basename($image->path)))]),
            'rating' => ['average' => round((float) $reviews->avg('rating'), 1), 'total' => $reviews->count(), 'breakdown' => $breakdown],
            'questions' => $product->questions,
            'related' => $related->map(fn ($item) => $this->cardData($item)),
            'recentlyViewed' => Product::whereIn('id', $recentIds)->get()->map(fn ($item) => $this->cardData($item)),
            'inWishlist' => $request->user() ? DB::table('wishlists')->where(['user_id' => $request->user()->id, 'product_id' => $product->id])->exists() : false,
            'stockSettings' => $this->stockSettings(),
        ]);
    }

    public function legacyShow(string $slug): RedirectResponse
    {
        $product = Product::query()->where('slug', $slug)->first();
        if (! $product) {
            $redirect = DB::table('product_url_redirects')->where('old_slug', $slug)->first();
            $product = $redirect ? Product::find($redirect->product_id) : null;
        }

        abort_unless($product && in_array($product->status, ['Published', 'Active']) && $product->visibility === 'Public', 404);

        return redirect()->route('storefront.products.show', $product->slug, 301);
    }

    public function cart(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1'], 'variant_id' => ['nullable', 'integer'], 'increment' => ['sometimes', 'boolean']]);
        abort_unless(in_array($product->status, ['Published', 'Active']) && $product->visibility === 'Public', 404);
        $hasVariants = $product->variants()->where('is_active', true)->exists();
        $variant = filled($data['variant_id'] ?? null) ? $product->variants()->where('is_active', true)->find($data['variant_id']) : null;
        if ($hasVariants && ! $variant) {
            return response()->json(['message' => 'Please select a product option.'], 422);
        }

        $cart = $request->session()->get('cart', []);
        $cartKey = $variant ? "{$product->id}:{$variant->id}" : (string) $product->id;
        if ($request->boolean('increment')) {
            $currentQuantity = (int) ($cart[$cartKey]['quantity'] ?? 0);
            $data['quantity'] = $currentQuantity > 0
                ? $currentQuantity + max(1, (int) $product->quantity_step)
                : max(1, (int) $product->min_order_quantity);
        }

        $settings = $this->stockSettings();
        $stock = $variant?->stock_quantity ?? $product->stock_quantity;
        $minimum = max(1, (int) $product->min_order_quantity);
        $step = max(1, (int) $product->quantity_step);
        $max = $settings['allowOutOfStockOrders'] ? ($product->max_order_quantity ?: PHP_INT_MAX) : min($stock, $product->max_order_quantity ?: $stock);
        $max = $max >= $minimum ? $minimum + intdiv($max - $minimum, $step) * $step : $max;
        if ($data['quantity'] < $minimum || $data['quantity'] > $max || (($data['quantity'] - $minimum) % $step) !== 0) {
            return response()->json(['message' => 'Selected quantity is not available.'], 422);
        }
        $unitPrice = $this->cartPricing->unitPrice($product, $variant, $data['quantity']);
        $cart[$cartKey] = ['product_id' => $product->id, 'variant_id' => $variant?->id, 'quantity' => $data['quantity'], 'unit_price' => $unitPrice, 'title' => $product->title, 'variant_name' => $variant?->name, 'slug' => $product->slug];
        $request->session()->put('cart', $cart);

        return response()->json(['message' => 'Product added to cart.', 'count' => collect($cart)->sum('quantity'), 'item' => $cart[$cartKey]]);
    }

    public function cartPage(Request $request): Response
    {
        return Inertia::render('app/modules/storefront/cart/pages/Index', $this->cartSummary($request));
    }

    public function cartSummaryJson(Request $request): JsonResponse
    {
        return response()->json($this->cartSummary($request));
    }

    public function updateCart(Request $request, string $cartKey): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $cart = $request->session()->get('cart', []);
        abort_unless(isset($cart[$cartKey]), 404);
        $line = $cart[$cartKey];
        $product = Product::findOrFail($line['product_id']);
        $variant = filled($line['variant_id'] ?? null) ? $product->variants()->where('is_active', true)->find($line['variant_id']) : null;
        if (filled($line['variant_id'] ?? null) && ! $variant) {
            return response()->json(['message' => 'This product option is no longer available.'], 422);
        }
        $settings = $this->stockSettings();
        $stock = $variant?->stock_quantity ?? $product->stock_quantity;
        $minimum = max(1, (int) $product->min_order_quantity);
        $step = max(1, (int) $product->quantity_step);
        $max = $settings['allowOutOfStockOrders'] ? ($product->max_order_quantity ?: PHP_INT_MAX) : min($stock, $product->max_order_quantity ?: $stock);
        $max = $max >= $minimum ? $minimum + intdiv($max - $minimum, $step) * $step : $max;
        if ($data['quantity'] < $minimum || $data['quantity'] > $max || (($data['quantity'] - $minimum) % $step) !== 0) {
            return response()->json(['message' => 'Selected quantity is not available.'], 422);
        }
        $cart[$cartKey]['quantity'] = $data['quantity'];
        $cart[$cartKey]['unit_price'] = $this->cartPricing->unitPrice($product, $variant, $data['quantity']);
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

    public function checkout(Request $request, CheckoutPhoneVerificationService $phoneVerification): Response|RedirectResponse
    {
        $summary = $this->cartSummary($request);
        if (empty($summary['items'])) {
            return redirect()->route('storefront.cart')->with('success', 'Your cart is empty.');
        }

        $checkoutToken = (string) $request->session()->get('checkout_token', '');
        if (! Str::isUuid($checkoutToken)) {
            $checkoutToken = (string) Str::uuid();
            $request->session()->put('checkout_token', $checkoutToken);
        }

        $summary['addresses'] = $request->user()?->addresses()->latest('is_default')->latest()->get() ?? [];
        $summary['customer'] = $request->user() ? ['name' => $request->user()->name, 'phone' => $request->user()->phone, 'email' => $request->user()->email] : null;
        $initialPhone = collect($summary['addresses'])->firstWhere('is_default', true)?->phone ?? ($summary['customer']['phone'] ?? '');
        $summary['phoneVerification'] = ['phone' => $initialPhone, 'verified' => $phoneVerification->isVerified($request, $initialPhone)];
        $summary['checkoutToken'] = $checkoutToken;

        return Inertia::render('app/modules/storefront/checkout/pages/Index', $summary);
    }

    public function placeOrder(Request $request, CheckoutPhoneVerificationService $phoneVerification): RedirectResponse
    {
        $request->merge(['phone' => $this->normalizeBangladeshPhone($request->input('phone'))]);
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:120'],
            'delivery_zone' => ['required', Rule::in(['inside_dhaka', 'outside_dhaka'])],
            'note' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::in(['cod'])],
            'address_id' => ['nullable', 'integer'],
            'save_address' => ['nullable', 'boolean'],
            'address_label' => ['nullable', 'string', 'max:60'],
            'district' => ['nullable', 'string', 'max:120'],
            'area' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'checkout_token' => ['required', 'uuid'],
        ]);

        $completedTokens = (array) $request->session()->get('completed_checkout_tokens', []);
        if (isset($completedTokens[$data['checkout_token']])) {
            return redirect()->route('storefront.order.success', $completedTokens[$data['checkout_token']]);
        }

        $sessionToken = (string) $request->session()->get('checkout_token', '');
        if ($sessionToken === '' || ! hash_equals($sessionToken, $data['checkout_token'])) {
            throw ValidationException::withMessages(['checkout_token' => 'Your checkout session expired. Refresh the checkout page and try again.']);
        }

        $finishCheckout = function (string $publicToken) use ($request, $phoneVerification, $data): RedirectResponse {
            $completedTokens = (array) $request->session()->get('completed_checkout_tokens', []);
            $completedTokens[$data['checkout_token']] = $publicToken;
            $request->session()->put('completed_checkout_tokens', array_slice($completedTokens, -5, null, true));
            $request->session()->forget(['cart', 'checkout_token']);
            $phoneVerification->clear($request);

            return redirect()->route('storefront.order.success', $publicToken);
        };

        $existingOrder = DB::table('orders')->where('checkout_token', $data['checkout_token'])->first(['public_token']);
        if ($existingOrder?->public_token) {
            return $finishCheckout($existingOrder->public_token);
        }

        $selectedAddress = null;
        if (filled($data['address_id'] ?? null)) {
            abort_unless($request->user(), 422);
            $selectedAddress = $request->user()->addresses()->find($data['address_id']);
            abort_unless($selectedAddress, 422, 'The selected delivery address is invalid.');
            $data['customer_name'] = $selectedAddress->recipient_name;
            $data['phone'] = $selectedAddress->phone;
            $data['address'] = $selectedAddress->address;
            $data['city'] = $selectedAddress->city;
            $data['delivery_zone'] = $selectedAddress->delivery_zone;
        }
        $data['phone'] = $this->validatedBangladeshPhone($data['phone']);
        if (! $selectedAddress) {
            $data['delivery_zone'] = app(DeliveryZoneDetector::class)->detect(
                $data['district'] ?? null,
                $data['city'],
                $data['address'],
            );
        }
        $phoneVerified = $phoneVerification->isVerified($request, $data['phone']);
        $cart = $request->session()->get('cart', []);
        if (! $cart) {
            return redirect()->route('storefront.cart')->with('success', 'Your cart is empty.');
        }

        $publicToken = Str::random(48);
        try {
            DB::transaction(function () use ($cart, $data, $request, $selectedAddress, $phoneVerified, $publicToken): void {
                $products = Product::query()->with(['bulkPrices', 'variants:id,product_id,is_active'])->whereIn('id', collect($cart)->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
                $variants = ProductVariant::query()->whereIn('id', collect($cart)->pluck('variant_id')->filter())->lockForUpdate()->get()->keyBy('id');
                $settings = $this->stockSettings();
                $subtotal = 0;
                $deliveryLines = collect();
                $hasStockShortage = false;

                foreach ($cart as $key => $line) {
                    $product = $products->get($line['product_id']);
                    $variant = filled($line['variant_id'] ?? null) ? $variants->get($line['variant_id']) : null;
                    $quantity = (int) ($line['quantity'] ?? 0);
                    if (! $product || ! in_array($product->status, ['Published', 'Active'], true) || $product->visibility !== 'Public') {
                        abort(422, 'One or more items are no longer available.');
                    }
                    if ($variant && (int) $variant->product_id !== (int) $product->id) {
                        abort(422, 'A selected product option is invalid.');
                    }
                    if ((filled($line['variant_id'] ?? null) && (! $variant || ! $variant->is_active)) || (! $variant && $product->variants->where('is_active', true)->isNotEmpty())) {
                        abort(422, 'Please select an available option for every product.');
                    }

                    $stock = (int) ($variant?->stock_quantity ?? $product->stock_quantity);
                    $minimum = max(1, (int) $product->min_order_quantity);
                    $step = max(1, (int) $product->quantity_step);
                    $configuredMaximum = (int) ($product->max_order_quantity ?: 0);
                    $maximum = $settings['allowOutOfStockOrders'] ? ($configuredMaximum ?: PHP_INT_MAX) : min($stock, $configuredMaximum ?: $stock);
                    $maximum = $maximum >= $minimum ? $minimum + intdiv($maximum - $minimum, $step) * $step : $maximum;
                    if ($quantity < $minimum || $quantity > $maximum || (($quantity - $minimum) % $step) !== 0) {
                        abort(422, 'One or more item quantities are no longer available.');
                    }

                    $price = $this->cartPricing->unitPrice($product, $variant, $quantity);
                    $cart[$key]['unit_price'] = $price;
                    $hasStockShortage = $hasStockShortage || $stock < $quantity;
                    $subtotal += $quantity * $price;
                    $deliveryLines->push(['product' => $product, 'quantity' => $quantity]);
                }

                $delivery = app(DeliveryChargeCalculator::class)->calculate(WebsiteSetting::firstOrCreate(['id' => 1]), $deliveryLines, $subtotal, $data['delivery_zone'], $data['payment_method']);
                $now = now();
                $orderId = DB::table('orders')->insertGetId([
                    'order_number' => 'TMP-'.Str::uuid(), 'checkout_token' => $data['checkout_token'], 'public_token' => $publicToken,
                    'user_id' => $request->user()?->id, 'customer_address_id' => $selectedAddress?->id,
                    'customer_name' => $data['customer_name'], 'phone' => $data['phone'], 'phone_verified_at' => $phoneVerified ? $now : null, 'email' => $data['email'] ?? null,
                    'address' => $data['address'], 'city' => $data['city'], 'delivery_zone' => $data['delivery_zone'], 'note' => $data['note'] ?? null,
                    'payment_method' => $data['payment_method'], 'payment_status' => 'pending', 'status' => 'pending',
                    'subtotal' => $subtotal, 'shipping_total' => $delivery['shipping'], 'cod_surcharge' => $delivery['cod'], 'delivery_breakdown' => json_encode($delivery), 'total' => $subtotal + $delivery['total'], 'has_stock_shortage' => $hasStockShortage,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('orders')->where('id', $orderId)->update(['order_number' => '#ORD'.str_pad((string) $orderId, 6, '0', STR_PAD_LEFT)]);

                if ($request->user() && ! $selectedAddress && ($data['save_address'] ?? false)) {
                    $makeDefault = ! $request->user()->addresses()->exists();
                    $address = $request->user()->addresses()->create([
                        'label' => $data['address_label'] ?: 'Home', 'recipient_name' => $data['customer_name'], 'phone' => $data['phone'],
                        'delivery_zone' => $data['delivery_zone'], 'district' => $data['district'] ?: $data['city'], 'city' => $data['city'],
                        'area' => $data['area'] ?? null, 'address' => $data['address'], 'postal_code' => $data['postal_code'] ?? null,
                        'landmark' => $data['landmark'] ?? null, 'is_default' => $makeDefault,
                    ]);
                    DB::table('orders')->where('id', $orderId)->update(['customer_address_id' => $address->id]);
                }
                foreach ($cart as $line) {
                    $product = $products->get($line['product_id']);
                    $variant = filled($line['variant_id'] ?? null) ? $variants->get($line['variant_id']) : null;
                    $stock = (int) ($variant?->stock_quantity ?? $product->stock_quantity);
                    $shortage = max(0, $line['quantity'] - $stock);
                    DB::table('order_items')->insert(['order_id' => $orderId, 'product_id' => $product->id, 'product_variant_id' => $variant?->id, 'product_title' => $product->title, 'variant_name' => $variant?->name, 'sku' => $variant?->sku ?: $product->sku, 'unit_price' => $line['unit_price'], 'quantity' => $line['quantity'], 'line_total' => $line['quantity'] * $line['unit_price'], 'stock_shortage_quantity' => $shortage, 'created_at' => $now, 'updated_at' => $now]);
                    if ($variant && $variant->stock_quantity > 0) {
                        $variant->decrement('stock_quantity', min($variant->stock_quantity, $line['quantity']));
                    } elseif (! $variant && $product->stock_quantity > 0) {
                        $product->decrement('stock_quantity', min($product->stock_quantity, $line['quantity']));
                    }
                }
            });
        } catch (QueryException $exception) {
            $existingOrder = DB::table('orders')->where('checkout_token', $data['checkout_token'])->first(['public_token']);
            if (! $existingOrder?->public_token) {
                throw $exception;
            }
            $publicToken = $existingOrder->public_token;
        }

        return $finishCheckout($publicToken);
    }

    public function orderSuccess(Request $request, string $orderToken): Response
    {
        $order = DB::table('orders')->where('public_token', $orderToken)->first(['order_number', 'customer_name', 'phone', 'total', 'status']);
        abort_unless($order, 404);

        return Inertia::render('app/modules/storefront/checkout/pages/Success', ['order' => $order]);
    }

    public function wishlist(Request $request, Product $product): JsonResponse
    {
        $existing = DB::table('wishlists')->where(['user_id' => $request->user()->id, 'product_id' => $product->id]);
        if ($existing->exists()) {
            $existing->delete();
            $active = false;
        } else {
            DB::table('wishlists')->insert(['user_id' => $request->user()->id, 'product_id' => $product->id, 'created_at' => now(), 'updated_at' => now()]);
            $active = true;
        }

        return response()->json(['active' => $active]);
    }

    public function wishlistPage(Request $request): Response
    {
        $ids = DB::table('wishlists')->where('user_id', $request->user()->id)->latest()->pluck('product_id');

        return Inertia::render('app/modules/customers/pages/Wishlist', ['products' => Product::with('brand:id,name')->whereIn('id', $ids)->get()->map(fn ($item) => $this->cardData($item))]);
    }

    public function comparePage(Request $request): Response
    {
        $query = DB::table('compare_products')->where($request->user() ? 'user_id' : 'session_id', $request->user()?->id ?? $request->session()->getId())->latest()->limit(4);
        $products = Product::with(['brand:id,name', 'specifications'])->whereIn('id', $query->pluck('product_id'))->get();

        return Inertia::render('app/modules/storefront/products/pages/ProductList', ['title' => 'Compare Products', 'compare' => true, 'products' => $products->map(fn ($item) => [...$this->cardData($item), 'specifications' => $item->specifications, 'warranty' => $item->warranty, 'stock' => $item->stock_quantity])]);
    }

    public function helpful(Request $request, ProductReview $review): JsonResponse
    {
        abort_unless($review->status === 'approved', 404);
        $key = ['review_id' => $review->id, 'user_id' => $request->user()?->id, 'session_id' => $request->user() ? null : $request->session()->getId()];
        if (DB::table('product_review_votes')->where($key)->exists()) {
            return response()->json(['message' => 'You already marked this review helpful.', 'helpful' => $review->helpful_count]);
        }
        DB::transaction(function () use ($key, $review) {
            DB::table('product_review_votes')->insert([...$key, 'created_at' => now(), 'updated_at' => now()]);
            $review->increment('helpful_count');
        });

        return response()->json(['message' => 'Thanks for your feedback.', 'helpful' => $review->fresh()->helpful_count]);
    }

    public function compare(Request $request, Product $product): JsonResponse
    {
        $key = ['product_id' => $product->id, 'user_id' => $request->user()?->id, 'session_id' => $request->user() ? null : $request->session()->getId()];
        $query = DB::table('compare_products')->where($key);
        if ($query->exists()) {
            $query->delete();
            $active = false;
        } else {
            DB::table('compare_products')->insert([...$key, 'created_at' => now(), 'updated_at' => now()]);
            $active = true;
        }

        return response()->json(['active' => $active]);
    }

    public function notify(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['email' => ['nullable', 'email', 'required_without:phone'], 'phone' => ['nullable', 'string', 'max:20', 'required_without:email']]);
        DB::table('stock_notifications')->insert(['product_id' => $product->id, 'user_id' => $request->user()?->id, 'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null, 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['message' => 'We will notify you when this product is available.'], 201);
    }

    public function review(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'title' => ['nullable', 'string', 'max:150'], 'description' => ['required', 'string', 'max:5000'], 'customer_name' => ['required', 'string', 'max:120'], 'customer_email' => ['nullable', 'email'], 'video_url' => ['nullable', 'url', 'max:255']]);
        $verified = false; // Order linkage is intentionally required before granting this badge.
        ProductReview::create([...$data, 'product_id' => $product->id, 'user_id' => $request->user()?->id, 'verified_purchase' => $verified, 'status' => 'pending']);

        return response()->json(['message' => 'Your review was submitted for approval.'], 201);
    }

    public function question(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['customer_name' => ['required', 'string', 'max:120'], 'customer_email' => ['nullable', 'email'], 'question' => ['required', 'string', 'max:2000']]);
        ProductQuestion::create([...$data, 'product_id' => $product->id, 'user_id' => $request->user()?->id, 'status' => 'pending']);

        return response()->json(['message' => 'Your question was submitted for approval.'], 201);
    }

    private function cartSummary(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        $products = Product::query()->with(['bulkPrices', 'variants:id,product_id,is_active'])->whereIn('id', collect($cart)->pluck('product_id'))->get()->keyBy('id');
        $variants = ProductVariant::query()->whereIn('id', collect($cart)->pluck('variant_id')->filter())->get()->keyBy('id');
        $settings = $this->stockSettings();
        $normalizedCart = [];
        $cartAdjusted = false;
        $items = collect($cart)->map(function (array $line, string|int $cartKey) use ($products, $variants, $settings, &$normalizedCart, &$cartAdjusted): ?array {
            $product = $products->get($line['product_id']);
            $variant = filled($line['variant_id'] ?? null) ? $variants->get($line['variant_id']) : null;
            $unavailable = ! $product
                || ! in_array($product->status, ['Published', 'Active'], true)
                || $product->visibility !== 'Public'
                || ($variant && (int) $variant->product_id !== (int) $product->id)
                || (filled($line['variant_id'] ?? null) && (! $variant || ! $variant->is_active))
                || (! $variant && $product->variants->where('is_active', true)->isNotEmpty());
            if ($unavailable) {
                $cartAdjusted = true;

                return null;
            }

            $stock = max(0, (int) ($variant?->stock_quantity ?? $product->stock_quantity));
            $minimum = max(1, (int) ($product->min_order_quantity ?: 1));
            $step = max(1, (int) ($product->quantity_step ?: 1));
            $configuredMaximum = (int) ($product->max_order_quantity ?: 0);
            $maximum = $settings['allowOutOfStockOrders'] ? ($configuredMaximum ?: PHP_INT_MAX) : min($stock, $configuredMaximum ?: $stock);
            $maximum = $maximum >= $minimum ? $minimum + intdiv($maximum - $minimum, $step) * $step : $maximum;
            if ($maximum < $minimum) {
                $cartAdjusted = true;

                return null;
            }

            $requestedQuantity = max($minimum, (int) ($line['quantity'] ?? $minimum));
            $requestedQuantity = min($requestedQuantity, $maximum);
            $quantity = $minimum + intdiv(max(0, $requestedQuantity - $minimum), $step) * $step;
            $price = $this->cartPricing->unitPrice($product, $variant, $quantity);
            $imagePath = $variant?->image_path ?: $product->featured_image_path;
            $normalizedCart[(string) $cartKey] = ['product_id' => $product->id, 'variant_id' => $variant?->id, 'quantity' => $quantity, 'unit_price' => $price, 'title' => $product->title, 'variant_name' => $variant?->name, 'slug' => $product->slug];
            if ($quantity !== (int) ($line['quantity'] ?? 0) || $price !== (float) ($line['unit_price'] ?? 0)) {
                $cartAdjusted = true;
            }

            return ['cart_key' => (string) $cartKey, 'product_id' => $product->id, 'variant_id' => $variant?->id, 'variant_name' => $variant?->name, 'title' => $product->title, 'slug' => $product->slug, 'quantity' => $quantity, 'unit_price' => $price, 'line_total' => $quantity * $price, 'stock_quantity' => $stock, 'min_quantity' => $minimum, 'quantity_step' => $step, 'max_quantity' => $maximum === PHP_INT_MAX ? null : $maximum, 'image' => $imagePath ? '/image/'.rawurlencode(basename($imagePath)) : null, 'weight' => (float) ($product->weight ?? 0), 'delivery_inside_dhaka' => $product->delivery_inside_dhaka !== null ? (float) $product->delivery_inside_dhaka : null, 'delivery_outside_dhaka' => $product->delivery_outside_dhaka !== null ? (float) $product->delivery_outside_dhaka : null];
        })->filter()->values();

        if ($normalizedCart !== $cart) {
            $request->session()->put('cart', $normalizedCart);
        }

        $delivery = WebsiteSetting::firstOrCreate(['id' => 1]);

        return ['items' => $items, 'subtotal' => $items->sum('line_total'), 'cartCount' => $items->sum('quantity'), 'cartNotice' => $cartAdjusted ? 'Your cart was updated to match current availability and pricing.' : null, 'deliverySettings' => [
            'enabled' => (bool) ($delivery->delivery_enabled ?? true), 'insideDhaka' => (float) ($delivery->delivery_inside_dhaka ?? 80), 'outsideDhaka' => (float) ($delivery->delivery_outside_dhaka ?? 150),
            'freeEnabled' => (bool) $delivery->free_delivery_enabled, 'freeThreshold' => $delivery->free_delivery_threshold !== null ? (float) $delivery->free_delivery_threshold : null,
            'codEnabled' => (bool) $delivery->cod_surcharge_enabled, 'codSurcharge' => (float) ($delivery->cod_surcharge ?? 0), 'productOverrideEnabled' => (bool) ($delivery->product_delivery_override_enabled ?? true),
            'heavyEnabled' => (bool) $delivery->heavy_delivery_enabled, 'heavyThreshold' => (float) ($delivery->heavy_weight_threshold ?? 5), 'heavyPerKg' => (float) ($delivery->heavy_charge_per_kg ?? 0),
        ]];
    }

    private function normalizeBangladeshPhone(mixed $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        return str_starts_with($digits, '880') ? '0'.substr($digits, 3) : $digits;
    }

    private function validatedBangladeshPhone(mixed $phone): string
    {
        $normalized = $this->normalizeBangladeshPhone($phone);
        if (! preg_match('/^01[3-9]\d{8}$/', $normalized)) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid Bangladesh phone number.']);
        }

        return $normalized;
    }

    private function cardData(Product $item): array
    {
        return ['id' => $item->id, 'title' => $item->title, 'slug' => $item->slug, 'image' => $item->featured_image_path ? '/image/'.rawurlencode(basename($item->featured_image_path)) : null, 'price' => $item->current_price, 'regularPrice' => (float) $item->regular_price, 'discount' => $item->discount_percentage, 'stockStatus' => $item->stock_status, 'brand' => $item->brand?->name];
    }

    private function productData(Product $p): array
    {
        $groups = $p->specifications
            ->groupBy(fn ($specification) => $specification->group_title ?: 'General Specifications')
            ->map(fn ($items, $title) => ['title' => $title, 'items' => $items->values()])
            ->values();

        return [...$p->toArray(), 'variants' => $p->variants->map(fn ($variant) => [...$variant->toArray(), 'image_url' => $this->mediaUrl($variant->image_path)])->values(), 'category_breadcrumb' => $this->categoryBreadcrumb($p), 'specifications' => [], 'specification_groups' => $groups, 'featured_image_url' => $this->mediaUrl($p->featured_image_path), 'gallery_urls' => collect($p->gallery ?? [])->filter()->map(fn ($path) => $this->mediaUrl($path))->values(), 'og_image_url' => $this->mediaUrl($p->og_image_path), 'twitter_image_url' => $this->mediaUrl($p->twitter_image_path)];
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

            while ($category && ! isset($visited[$category->id])) {
                $visited[$category->id] = true;
                array_unshift($path, ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug]);
                $category = $category->parent_id ? $categories->get($category->parent_id) : null;
            }

            if (count($path) > count($deepestPath)) {
                $deepestPath = $path;
            }
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
        if (! $path) {
            return null;
        }
        $normalizedPath = ltrim(str_replace('\\', '/', preg_replace('#^/?storage/#', '', $path)), '/');
        $version = is_file(storage_path('app/public/'.$normalizedPath)) ? filemtime(storage_path('app/public/'.$normalizedPath)) : 1;
        $encodedPath = collect(explode('/', $normalizedPath))->map(fn ($segment) => rawurlencode($segment))->implode('/');

        return '/image/'.rawurlencode(basename($normalizedPath)).'?v='.$version;
    }
}
