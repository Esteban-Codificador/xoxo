<?php

use App\Domain\Curriculum\Actions\PublishLesson;
use App\Http\Controllers\Dev\DesignSystemController;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

it('is not registered outside the local environment', function () {
    expect(Route::has('dev.design-system'))->toBeFalse();

    $this->get('/_dev/design-system')->assertNotFound();
});

it('renders a published lesson body next to the components', function () {
    Route::middleware('web')->get('/_test/design-system', DesignSystemController::class);

    $first = publishableLesson(['title' => 'Commits', 'slug' => 'commits']);
    $second = publishableLesson(['title' => 'Ramas', 'slug' => 'ramas']);
    app(PublishLesson::class)->handle($first);
    app(PublishLesson::class)->handle($second);
    publishableLesson(['title' => 'Borrador', 'slug' => 'borrador']);

    $this->get('/_test/design-system?lesson=ramas')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dev/design-system')
            ->has('lessons', 2)
            ->where('lesson.slug', 'ramas')
            ->where('lesson.title', 'Ramas')
            ->where('lesson.body.version', 1)
            ->where('lesson.body.doc.type', 'doc'));
});

it('shows an empty state when nothing is published', function () {
    Route::middleware('web')->get('/_test/design-system', DesignSystemController::class);

    $this->get('/_test/design-system')
        ->assertInertia(fn (Assert $page) => $page->where('lesson', null)->has('lessons', 0));
});
