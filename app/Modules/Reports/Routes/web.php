<?php

use App\Modules\Shared\Http\Controllers\ModulePageController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/reports', ModulePageController::class)->defaults('module', 'reports')->name('reports.index');
