<?php

use App\Modules\Customers\Http\Controllers\AdminCustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/customers', AdminCustomerController::class)->name('customers.index');