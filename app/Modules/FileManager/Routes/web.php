<?php

use App\Modules\FileManager\Http\Controllers\FileManagerController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/file-manager', FileManagerController::class)->name('file-manager.index');
Route::get('/admin/file-manager/media', [FileManagerController::class, 'media'])->name('file-manager.media');
Route::patch('/admin/file-manager/{media}', [FileManagerController::class, 'update'])->name('file-manager.update');
Route::delete('/admin/file-manager/{media}', [FileManagerController::class, 'destroy'])->name('file-manager.destroy');
