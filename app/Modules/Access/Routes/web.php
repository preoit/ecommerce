<?php

use App\Modules\Access\Http\Controllers\AdminUserController;
use App\Modules\Access\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/users')->name('admin-users.')->group(function (): void {
    Route::get('/', [AdminUserController::class, 'index'])->name('index');
    Route::get('/create', [AdminUserController::class, 'create'])->name('create');
    Route::post('/', [AdminUserController::class, 'store'])->name('store');
    Route::get('/{adminUser}', [AdminUserController::class, 'show'])->name('show');
    Route::get('/{adminUser}/edit', [AdminUserController::class, 'edit'])->name('edit');
    Route::put('/{adminUser}', [AdminUserController::class, 'update'])->name('update');
    Route::delete('/{adminUser}', [AdminUserController::class, 'destroy'])->name('destroy');
});

Route::prefix('admin/roles')->name('roles.')->group(function (): void {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::get('/create', [RoleController::class, 'create'])->name('create');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('/{role}', [RoleController::class, 'show'])->name('show');
    Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
});
