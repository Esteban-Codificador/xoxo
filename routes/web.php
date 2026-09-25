<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Dev\DesignSystemController;
use App\Http\Controllers\Learn\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('can:'.Permission::AdminAccess->value)
        ->group(function () {
            Route::get('/', AdminDashboardController::class)->name('dashboard');
        });
});

// Local-only review tools. Never registered in testing or production.
if (app()->isLocal()) {
    Route::get('_dev/design-system', DesignSystemController::class)->name('dev.design-system');
}

require __DIR__.'/settings.php';
