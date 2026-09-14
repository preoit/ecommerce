<?php
use Illuminate\Support\Facades\Route;
use App\Modules\Courier\CourierController;
Route::prefix('admin/couriers')->name('couriers.')->group(function () {
    Route::get('/',[CourierController::class,'index'])->name('index');
    Route::get('/settings',[CourierController::class,'settings'])->name('settings');
    Route::patch('/settings/{courier}',[CourierController::class,'save'])->name('save');
    Route::post('/settings/{courier}/test',[CourierController::class,'test'])->middleware('throttle:6,1')->name('test');
    Route::get('/{courier}/locations',[CourierController::class,'locations'])->name('locations');
    Route::get('/order/{order}',[CourierController::class,'order'])->name('order');
    Route::post('/book',[CourierController::class,'book'])->middleware('throttle:10,1')->name('book');
    Route::post('/bookings/{booking}/refresh',[CourierController::class,'refresh'])->middleware('throttle:10,1')->name('refresh');
    Route::post('/bookings/{booking}/cancel',[CourierController::class,'cancel'])->name('cancel');
    Route::post('/bookings/{booking}/reconcile',[CourierController::class,'reconcile'])->name('reconcile');
    Route::post('/bookings/{booking}/settlement',[CourierController::class,'settlement'])->name('settlement');
    Route::get('/bookings/{booking}/label',[CourierController::class,'label'])->name('label');
});
