<?php
use App\Modules\Blog\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/categories', [BlogController::class, 'categories'])->name('categories');
    Route::post('/categories', [BlogController::class, 'saveCategory'])->name('categories.store');
    Route::patch('/categories/{category}', [BlogController::class, 'saveCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [BlogController::class, 'deleteCategory'])->name('categories.destroy');
    Route::post('/authors', [BlogController::class, 'saveAuthor'])->name('authors.store');
    Route::patch('/authors/{author}', [BlogController::class, 'saveAuthor'])->name('authors.update');
    Route::get('/create', [BlogController::class, 'form'])->name('create');
    Route::post('/', [BlogController::class, 'save'])->name('store');
    Route::get('/{post}/edit', [BlogController::class, 'form'])->name('edit');
    Route::patch('/{post}', [BlogController::class, 'save'])->name('update');
    Route::delete('/{post}', [BlogController::class, 'destroy'])->name('destroy');
});
