<?php

use App\Http\Controllers\SalesPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('sales-pages.index')
        : redirect()->route('login');
});

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::resource('sales-pages', SalesPageController::class)
        ->except(['edit', 'update']);

    // Bonus features
    Route::get('sales-pages/{salesPage}/export', [SalesPageController::class, 'exportHtml'])
        ->name('sales-pages.export');

    Route::post('sales-pages/{salesPage}/regenerate-section', [SalesPageController::class, 'regenerateSection'])
        ->name('sales-pages.regenerate-section');
});
