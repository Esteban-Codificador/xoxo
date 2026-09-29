<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Dev\DesignSystemController;
use App\Http\Controllers\Learn\CurrentRoadmapController;
use App\Http\Controllers\Learn\DashboardController;
use App\Http\Controllers\Learn\LessonProgressController;
use App\Http\Controllers\Learn\ShowLessonController;
use App\Http\Controllers\Learn\ShowRoadmapController;
use App\Http\Controllers\Learn\ShowTrackController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('roadmap', CurrentRoadmapController::class)->name('roadmap');
    Route::get('roadmaps/{roadmap:slug}', ShowRoadmapController::class)->name('roadmaps.show');
    Route::get('roadmaps/{roadmap:slug}/tracks/{track:slug}', ShowTrackController::class)
        ->scopeBindings()
        ->name('tracks.show');
    Route::get('lessons/{lesson:slug}', ShowLessonController::class)->name('lessons.show');

    Route::controller(LessonProgressController::class)->prefix('lessons/{lesson:slug}')->name('lessons.')->group(function () {
        Route::post('start', 'start')->name('start');
        Route::post('complete', 'complete')->name('complete');
        Route::delete('complete', 'uncomplete')->name('uncomplete');
    });

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
