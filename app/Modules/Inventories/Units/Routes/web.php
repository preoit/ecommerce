<?php

use App\Modules\Inventories\Units\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/inventories/units', [UnitController::class, 'index'])->name('inventories.units.index');
Route::post('/admin/inventories/units', [UnitController::class, 'store'])->name('inventories.units.store');
Route::put('/admin/inventories/units/{unit}', [UnitController::class, 'update'])->name('inventories.units.update');
Route::delete('/admin/inventories/units/{unit}', [UnitController::class, 'destroy'])->name('inventories.units.destroy');
