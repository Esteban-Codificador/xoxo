<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\ModuleController as AdminModuleController;
use App\Http\Controllers\Admin\ModuleOrderController;
use App\Http\Controllers\Admin\ModuleStatusController;
use App\Http\Controllers\Admin\PublishLessonController;
use App\Http\Controllers\Admin\TrackController as AdminTrackController;
use App\Http\Controllers\Admin\TrackDependencyController;
use App\Http\Controllers\Admin\TrackStatusController;
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

            Route::get('lessons', [AdminLessonController::class, 'index'])->name('lessons.index');
            Route::get('lessons/{lesson:slug}/edit', [AdminLessonController::class, 'edit'])->name('lessons.edit');
            Route::put('lessons/{lesson:slug}', [AdminLessonController::class, 'update'])->name('lessons.update');
            Route::post('lessons/{lesson:slug}/publish', PublishLessonController::class)->name('lessons.publish');

            // Tracks and modules by id: their slugs are only unique inside the parent and editable.
            Route::get('tracks', [AdminTrackController::class, 'index'])->name('tracks.index');
            Route::get('tracks/{track}/edit', [AdminTrackController::class, 'edit'])->name('tracks.edit');
            Route::put('tracks/{track}', [AdminTrackController::class, 'update'])->name('tracks.update');
            Route::put('tracks/{track}/status', TrackStatusController::class)->name('tracks.status');
            Route::put('tracks/{track}/dependencies', TrackDependencyController::class)->name('tracks.dependencies');
            Route::put('tracks/{track}/module-order', ModuleOrderController::class)->name('tracks.module-order');
            Route::put('modules/{module}', [AdminModuleController::class, 'update'])->name('modules.update');
            Route::put('modules/{module}/status', ModuleStatusController::class)->name('modules.status');
        });
});

// Local-only review tools. Never registered in testing or production.
if (app()->isLocal()) {
    Route::get('_dev/design-system', DesignSystemController::class)->name('dev.design-system');
}

require __DIR__.'/settings.php';
