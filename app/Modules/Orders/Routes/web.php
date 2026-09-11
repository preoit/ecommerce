<?php

use App\Modules\Orders\Http\Controllers\OrderDetailsController;
use App\Modules\Orders\Http\Controllers\OrderNotificationController;
use App\Modules\Orders\Http\Controllers\ManualOrderController;
use App\Modules\Orders\Http\Controllers\OrderIndexController;
use App\Modules\Orders\Http\Controllers\QuickCustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/orders', OrderIndexController::class)->name('orders.index');
Route::get('/admin/orders/notifications', [OrderNotificationController::class, 'index'])->name('orders.notifications');
Route::get('/admin/orders/create', [ManualOrderController::class, 'create'])->name('orders.create');
Route::post('/admin/orders/customers', [QuickCustomerController::class, 'store'])->name('orders.customers.store');
Route::post('/admin/orders', [ManualOrderController::class, 'store'])->name('orders.store');
Route::post('/admin/orders/{order}/view', [OrderNotificationController::class, 'markViewed'])->name('orders.view');
Route::get('/admin/orders/{order}/shipping-label', [OrderDetailsController::class, 'shippingLabel'])->name('orders.shipping-label.show');
Route::post('/admin/orders/{order}/shipping-label', [OrderDetailsController::class, 'generateShippingLabel'])->name('orders.shipping-label.generate');
Route::get('/admin/orders/{order}', [OrderDetailsController::class, 'show'])->name('orders.show');
Route::patch('/admin/orders/{order}/status', [OrderDetailsController::class, 'updateStatus'])->name('orders.status.update');
Route::patch('/admin/orders/{order}/payment-status', [OrderDetailsController::class, 'updatePaymentStatus'])->name('orders.payment-status.update');
