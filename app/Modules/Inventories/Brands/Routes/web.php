<?php

use App\Modules\Inventories\Brands\Http\Controllers\BrandController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/inventories/brands', [BrandController::class, 'index'])->name('inventories.brands.index');
Route::post('/admin/inventories/brands', [BrandController::class, 'store'])->name('inventories.brands.store');
Route::put('/admin/inventories/brands/{brand}', [BrandController::class, 'update'])->name('inventories.brands.update');
Route::delete('/admin/inventories/brands/{brand}', [BrandController::class, 'destroy'])->name('inventories.brands.destroy');
