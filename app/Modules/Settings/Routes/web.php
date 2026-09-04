<?php

use App\Modules\Settings\Http\Controllers\WebsiteSettingController;
use App\Modules\Settings\Http\Controllers\HeroSectionController;
use App\Modules\Settings\Http\Controllers\FooterSectionController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/settings', [WebsiteSettingController::class, 'index'])->name('settings.index');

Route::get('/admin/settings/website', [WebsiteSettingController::class, 'index'])->name('settings.website');
Route::patch('/admin/settings/website', [WebsiteSettingController::class, 'update'])->name('settings.website.update');
Route::post('/admin/settings/website/media', [WebsiteSettingController::class, 'upload'])->name('settings.website.media.upload');

Route::get('/admin/website-design/hero-section', [HeroSectionController::class, 'edit'])->name('website-design.hero.edit');
Route::patch('/admin/website-design/hero-section', [HeroSectionController::class, 'update'])->name('website-design.hero.update');
Route::get('/admin/website-design/footer', [FooterSectionController::class, 'edit'])->name('website-design.footer.edit');
Route::patch('/admin/website-design/footer', [FooterSectionController::class, 'update'])->name('website-design.footer.update');
