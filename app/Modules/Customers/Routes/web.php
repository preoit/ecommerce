
<?php

use App\Modules\Shared\Http\Controllers\ModulePageController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/customers', ModulePageController::class)->defaults('module', 'customers')->name('customers.index');
