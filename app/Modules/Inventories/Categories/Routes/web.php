<?php

use App\Modules\Inventories\Categories\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/inventories/categories', [CategoryController::class, 'index'])->name('inventories.categories.index');
Route::get('/admin/inventories/categories/create', [CategoryController::class, 'create'])->name('inventories.categories.create');
Route::get('/admin/inventories/categories/{category}/edit', [CategoryController::class, 'edit'])->name('inventories.categories.edit');
Route::post('/admin/inventories/categories', [CategoryController::class, 'store'])->name('inventories.categories.store');
Route::put('/admin/inventories/categories/{category}', [CategoryController::class, 'update'])->name('inventories.categories.update');
Route::delete('/admin/inventories/categories/{category}', [CategoryController::class, 'destroy'])->name('inventories.categories.destroy');
