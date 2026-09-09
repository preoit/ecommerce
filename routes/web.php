<?php

use App\Http\Controllers\ProfileController;
use App\Modules\Customers\Http\Controllers\CustomerAccountController;

use App\Modules\Settings\Http\Controllers\MediaImageController;
use App\Modules\Inventories\Products\Http\Controllers\StorefrontProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('app/modules/storefront/pages/Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'featuredProducts' => \Illuminate\Support\Facades\Schema::hasTable('products') ? \App\Modules\Inventories\Products\Models\Product::with(['categories:id,name','brand:id,name'])->whereIn('status',['Published','Active'])->where('visibility','Public')->latest('published_at')->latest('id')->limit(8)->get()->map(fn($product)=>['id'=>$product->id,'name'=>$product->title,'slug'=>$product->slug,'category'=>$product->categories->first()?->name??'Products','brand'=>$product->brand?->name,'price'=>(float)$product->current_price,'regularPrice'=>(float)$product->regular_price,'discount'=>$product->discount_percentage,'stockStatus'=>$product->stock_status,'isNewArrival'=>$product->is_new_arrival,'image'=>$product->featured_image_path?'/image/'.rawurlencode(basename($product->featured_image_path)):null]) : [],
    ]);
})->name('storefront.home');

Route::get('/image/{filename}', MediaImageController::class)
    ->where('filename', '[^/]+')
    ->name('media.image');

Route::middleware(['auth', 'admin'])->group(base_path('app/Modules/routes.php'));

Route::middleware('auth')->group(function () {
    Route::get('/profile', fn () => redirect()->route(request()->user()->is_admin ? 'admin.profile.edit' : 'account.profile.edit'));

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/profile', [ProfileController::class, 'adminEdit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

require __DIR__.'/auth.php';
Route::middleware(['auth', 'customer'])->prefix('account')->name('account.')->group(function () {
    Route::get('/profile', [ProfileController::class, 'customerEdit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/', [CustomerAccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [CustomerAccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [CustomerAccountController::class, 'order'])->name('orders.show');
    Route::get('/addresses', [CustomerAccountController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [CustomerAccountController::class, 'storeAddress'])->name('addresses.store');
    Route::patch('/addresses/{address}', [CustomerAccountController::class, 'updateAddress'])->name('addresses.update');
    Route::patch('/addresses/{address}/default', [CustomerAccountController::class, 'defaultAddress'])->name('addresses.default');
    Route::delete('/addresses/{address}', [CustomerAccountController::class, 'destroyAddress'])->name('addresses.destroy');
    Route::get('/reviews', [CustomerAccountController::class, 'reviews'])->name('reviews');
});

Route::get('/products', [StorefrontProductController::class, 'index'])->name('storefront.products.index');
Route::get('/cart', [StorefrontProductController::class, 'cartPage'])->name('storefront.cart');
Route::patch('/cart/{cartKey}', [StorefrontProductController::class, 'updateCart'])->middleware('throttle:30,1')->name('storefront.cart.update');
Route::delete('/cart/{cartKey}', [StorefrontProductController::class, 'removeCart'])->middleware('throttle:30,1')->name('storefront.cart.remove');
Route::get('/checkout', [StorefrontProductController::class, 'checkout'])->name('storefront.checkout');
Route::post('/checkout', [StorefrontProductController::class, 'placeOrder'])->middleware('throttle:10,1')->name('storefront.checkout.place-order');
Route::get('/order/{orderNumber}/success', [StorefrontProductController::class, 'orderSuccess'])->name('storefront.order.success');
Route::get('/product/{slug}', [StorefrontProductController::class, 'legacyShow'])->name('storefront.products.legacy');
Route::post('/product/{product}/cart', [StorefrontProductController::class, 'cart'])->middleware('throttle:30,1')->name('storefront.products.cart');
Route::post('/product/{product}/compare', [StorefrontProductController::class, 'compare'])->middleware('throttle:30,1')->name('storefront.products.compare');
Route::post('/product/{product}/notify', [StorefrontProductController::class, 'notify'])->middleware('throttle:5,1')->name('storefront.products.notify');
Route::post('/product/{product}/reviews', [StorefrontProductController::class, 'review'])->middleware('throttle:3,1')->name('storefront.products.reviews.store');
Route::post('/product/{product}/questions', [StorefrontProductController::class, 'question'])->middleware('throttle:5,1')->name('storefront.products.questions.store');
Route::post('/product/{product}/wishlist', [StorefrontProductController::class, 'wishlist'])->middleware(['auth','throttle:30,1'])->name('storefront.products.wishlist');
Route::post('/reviews/{review}/helpful', [StorefrontProductController::class, 'helpful'])->middleware('throttle:20,1')->name('storefront.reviews.helpful');
Route::get('/wishlist', [StorefrontProductController::class, 'wishlistPage'])->middleware('auth')->name('storefront.wishlist');
Route::get('/compare', [StorefrontProductController::class, 'comparePage'])->name('storefront.compare');

Route::get('/sitemap-products.xml', function () {
    $products = \App\Modules\Inventories\Products\Models\Product::whereIn('status',['Published','Active'])->where('visibility','Public')->where('meta_robots','like','index%')->get(['slug','updated_at']);
    return response()->view('sitemaps.products', compact('products'))->header('Content-Type','application/xml');
})->name('sitemap.products');

Route::get('/{slug}', [StorefrontProductController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('storefront.products.show');
