<?php

use App\Modules\Settings\Http\Controllers\WebsiteSettingController;
use App\Modules\Settings\Http\Controllers\CommunicationSettingController;
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

Route::get('/admin/settings/communication', [CommunicationSettingController::class, 'edit'])->name('settings.communication');
Route::patch('/admin/settings/communication', [CommunicationSettingController::class, 'update'])->name('settings.communication.update');
Route::post('/admin/settings/communication/test-email', [CommunicationSettingController::class, 'testEmail'])->name('settings.communication.test-email');
Route::post('/admin/settings/communication/test-sms', [CommunicationSettingController::class, 'testSms'])->name('settings.communication.test-sms');
