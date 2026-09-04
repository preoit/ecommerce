<?php

use App\Modules\Inventories\Products\Http\Controllers\CreateProductController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/inventories/products', [CreateProductController::class, 'index'])
    ->name('inventories.products.index');

Route::get('/admin/inventories/products/create', CreateProductController::class)
    ->name('inventories.products.create');
Route::post('/admin/inventories/products', [CreateProductController::class, 'store'])->name('inventories.products.store');
Route::patch('/admin/inventories/products/{product}', [CreateProductController::class, 'update'])->name('inventories.products.update');
Route::delete('/admin/inventories/products/{product}', [CreateProductController::class, 'destroy'])->name('inventories.products.destroy');
Route::post('/admin/inventories/products/categories', [CreateProductController::class, 'storeCategory'])->name('inventories.products.categories.store');
Route::post('/admin/inventories/products/brands', [CreateProductController::class, 'storeBrand'])->name('inventories.products.brands.store');
