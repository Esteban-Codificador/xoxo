<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\LessonRelationsController;
use App\Http\Controllers\Admin\LessonReviewController;
use App\Http\Controllers\Admin\LessonStatusController;
use App\Http\Controllers\Admin\LessonVersionController;
use App\Http\Controllers\Admin\ModuleController as AdminModuleController;
use App\Http\Controllers\Admin\ModuleOrderController;
use App\Http\Controllers\Admin\ModuleStatusController;
use App\Http\Controllers\Admin\PublishLessonController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\ResourceStatusController;
use App\Http\Controllers\Admin\ResourceVerificationController;
use App\Http\Controllers\Admin\ReviewQueueController;
use App\Http\Controllers\Admin\SkillController as AdminSkillController;
use App\Http\Controllers\Admin\SkillDependencyController;
use App\Http\Controllers\Admin\SkillStatusController;
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
            Route::get('lessons/create', [AdminLessonController::class, 'create'])->name('lessons.create');
            Route::post('lessons', [AdminLessonController::class, 'store'])->name('lessons.store');
            Route::get('lessons/{lesson:slug}/edit', [AdminLessonController::class, 'edit'])->name('lessons.edit');
            Route::put('lessons/{lesson:slug}', [AdminLessonController::class, 'update'])->name('lessons.update');
            Route::post('lessons/{lesson:slug}/publish', PublishLessonController::class)->name('lessons.publish');
            Route::put('lessons/{lesson:slug}/status', LessonStatusController::class)->name('lessons.status');
            Route::get('lessons/{lesson:slug}/relations', [LessonRelationsController::class, 'edit'])->name('lessons.relations.edit');
            Route::put('lessons/{lesson:slug}/relations', [LessonRelationsController::class, 'update'])->name('lessons.relations.update');
            Route::get('lessons/{lesson:slug}/changes', [LessonVersionController::class, 'changes'])->name('lessons.changes');
            Route::post('lessons/{lesson:slug}/review', [LessonReviewController::class, 'store'])->name('lessons.review.store');
            Route::post('lessons/{lesson:slug}/review/return', [LessonReviewController::class, 'sendBack'])->name('lessons.review.return');
            Route::delete('lessons/{lesson:slug}/review', [LessonReviewController::class, 'destroy'])->name('lessons.review.destroy');
            Route::get('reviews', ReviewQueueController::class)->name('reviews.index');
            Route::get('lessons/{lesson:slug}/versions/{version:version}', [LessonVersionController::class, 'show'])
                ->scopeBindings()
                ->name('lessons.versions.show');

            // Tracks and modules by id: their slugs are only unique inside the parent and editable.
            Route::get('tracks', [AdminTrackController::class, 'index'])->name('tracks.index');
            Route::get('tracks/create', [AdminTrackController::class, 'create'])->name('tracks.create');
            Route::post('tracks', [AdminTrackController::class, 'store'])->name('tracks.store');
            Route::get('tracks/{track}/edit', [AdminTrackController::class, 'edit'])->name('tracks.edit');
            Route::put('tracks/{track}', [AdminTrackController::class, 'update'])->name('tracks.update');
            Route::put('tracks/{track}/status', TrackStatusController::class)->name('tracks.status');
            Route::put('tracks/{track}/dependencies', TrackDependencyController::class)->name('tracks.dependencies');
            Route::put('tracks/{track}/module-order', ModuleOrderController::class)->name('tracks.module-order');
            Route::post('tracks/{track}/modules', [AdminModuleController::class, 'store'])->name('tracks.modules.store');
            Route::put('modules/{module}', [AdminModuleController::class, 'update'])->name('modules.update');
            Route::put('modules/{module}/status', ModuleStatusController::class)->name('modules.status');

            Route::get('skills', [AdminSkillController::class, 'index'])->name('skills.index');
            Route::get('skills/create', [AdminSkillController::class, 'create'])->name('skills.create');
            Route::post('skills', [AdminSkillController::class, 'store'])->name('skills.store');
            Route::get('skills/{skill}/edit', [AdminSkillController::class, 'edit'])->name('skills.edit');
            Route::put('skills/{skill}', [AdminSkillController::class, 'update'])->name('skills.update');
            Route::put('skills/{skill}/status', SkillStatusController::class)->name('skills.status');
            Route::put('skills/{skill}/dependencies', SkillDependencyController::class)->name('skills.dependencies');

            Route::get('resources', [ResourceController::class, 'index'])->name('resources.index');
            Route::get('resources/create', [ResourceController::class, 'create'])->name('resources.create');
            Route::post('resources', [ResourceController::class, 'store'])->name('resources.store');
            Route::get('resources/{resource}/edit', [ResourceController::class, 'edit'])->name('resources.edit');
            Route::put('resources/{resource}', [ResourceController::class, 'update'])->name('resources.update');
            Route::put('resources/{resource}/status', ResourceStatusController::class)->name('resources.status');
            Route::post('resources/{resource}/verify', ResourceVerificationController::class)->name('resources.verify');
        });
});

// Local-only review tools. Never registered in testing or production.
if (app()->isLocal()) {
    Route::get('_dev/design-system', DesignSystemController::class)->name('dev.design-system');
}

require __DIR__.'/settings.php';
