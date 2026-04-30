<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesPageController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('sales-pages.index')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    // Dashboard alias — keeps Breeze-style links working.
    Route::redirect('/dashboard', '/sales-pages')->name('dashboard');

    Route::resource('sales-pages', SalesPageController::class)
        ->except(['edit', 'update']);

    Route::get('sales-pages/{salesPage}/export', [SalesPageController::class, 'exportHtml'])
        ->name('sales-pages.export');

    Route::post('sales-pages/{salesPage}/regenerate-section', [SalesPageController::class, 'regenerateSection'])
        ->name('sales-pages.regenerate-section');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings/logo', [SettingsController::class, 'destroyLogo'])->name('settings.destroy-logo');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
