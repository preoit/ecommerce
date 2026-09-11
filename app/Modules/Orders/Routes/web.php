<?php

use App\Modules\Shared\Http\Controllers\ModulePageController;
use App\Modules\Orders\Http\Controllers\OrderDetailsController;
use App\Modules\Orders\Http\Controllers\OrderNotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/orders', ModulePageController::class)->defaults('module', 'orders')->name('orders.index');
Route::get('/admin/orders/notifications', [OrderNotificationController::class, 'index'])->name('orders.notifications');
Route::post('/admin/orders/{order}/view', [OrderNotificationController::class, 'markViewed'])->name('orders.view');
Route::get('/admin/orders/{order}', [OrderDetailsController::class, 'show'])->name('orders.show');
Route::patch('/admin/orders/{order}/status', [OrderDetailsController::class, 'updateStatus'])->name('orders.status.update');
Route::post('/admin/orders/{order}/shipping-label', [OrderDetailsController::class, 'generateShippingLabel'])->name('orders.shipping-label.generate');
