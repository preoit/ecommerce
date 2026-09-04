<?php

use App\Modules\Shared\Http\Controllers\ModulePageController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/coupons', ModulePageController::class)->defaults('module', 'coupons')->name('coupons.index');
