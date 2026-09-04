<?php

use App\Modules\Reviews\Http\Controllers\ReviewModerationController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/reviews', [ReviewModerationController::class,'index'])->name('reviews.index');
Route::patch('/admin/reviews/{review}', [ReviewModerationController::class,'review'])->name('reviews.update');
Route::delete('/admin/reviews/{review}', [ReviewModerationController::class,'destroyReview'])->name('reviews.destroy');
Route::patch('/admin/questions/{question}', [ReviewModerationController::class,'question'])->name('questions.update');
Route::delete('/admin/questions/{question}', [ReviewModerationController::class,'destroyQuestion'])->name('questions.destroy');
