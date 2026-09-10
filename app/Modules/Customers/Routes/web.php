<?php

use App\Modules\Customers\Http\Controllers\AdminCustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
Route::get('/admin/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
Route::patch('/admin/customers/{customer}', [AdminCustomerController::class, 'update'])->name('customers.update');
Route::delete('/admin/customers/{customer}', [AdminCustomerController::class, 'destroy'])->name('customers.destroy');