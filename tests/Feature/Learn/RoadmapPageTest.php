<?php

use App\Domain\Learning\Actions\CompleteLesson;
use App\Enums\ContentStatus;
use App\Enums\DependencyKind;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Track;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->roadmap = Roadmap::factory()->published()->create(['slug' => 'ai-engineer', 'title' => 'AI Engineer']);
    $this->base = Track::factory()->published()->for($this->roadmap)->create(['slug' => 'base', 'title' => 'Base', 'position' => 0]);
    $this->next = Track::factory()->published()->for($this->roadmap)->create(['slug' => 'siguiente', 'title' => 'Siguiente', 'position' => 1]);
    $this->draft = Track::factory()->for($this->roadmap)->create(['slug' => 'borrador', 'position' => 2]);

    $this->next->prerequisites()->attach($this->base, ['kind' => DependencyKind::Required->value, 'min_progress' => 100]);
    $this->draft->prerequisites()->attach($this->base, ['kind' => DependencyKind::Recommended->value, 'min_progress' => 50]);

    $module = Module::factory()->published()->for($this->base)->create(['slug' => 'intro', 'title' => 'Introducción']);
    $this->first = publishedLesson(['slug' => 'primera', 'title' => 'Primera', 'position' => 1], $module);
    publishedLesson(['slug' => 'segunda', 'title' => 'Segunda', 'position' => 2], $module);

    $this->user = User::factory()->create();
});

it('sends guests to the login page', function () {
    $this->get('/roadmaps/ai-engineer')->assertRedirect(route('login'));
});

it('lists published tracks with their state, lessons and the edges between them', function () {
    app(CompleteLesson::class)->handle($this->user, $this->first);

    $this->actingAs($this->user)
        ->get('/roadmaps/ai-engineer')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('roadmap/show')
            ->where('roadmap.title', 'AI Engineer')
            ->where('policy', 'ADVISORY')
            ->has('tracks', 2)
            ->where('tracks.0.slug', 'base')
            ->where('tracks.0.progress.state', 'IN_PROGRESS')
            ->where('tracks.0.progress.progress', 50)
            ->where('tracks.0.lessons', [
                ['slug' => 'primera', 'title' => 'Primera', 'module_slug' => 'intro', 'module_title' => 'Introducción', 'state' => 'COMPLETED'],
                ['slug' => 'segunda', 'title' => 'Segunda', 'module_slug' => 'intro', 'module_title' => 'Introducción', 'state' => 'AVAILABLE'],
            ])
            ->where('tracks.0.continue.slug', 'segunda')
            ->where('tracks.1.progress.state', 'LOCKED')
            ->where('tracks.1.progress.blockers.0.slug', 'base')
            // The edge to the draft track is left out: it has no node to point at.
            ->where('edges', [['from' => 'base', 'to' => 'siguiente', 'kind' => 'REQUIRED', 'min_progress' => 100]]));
});

it('answers 404 for a draft roadmap', function () {
    $this->roadmap->update(['status' => ContentStatus::Draft]);

    $this->actingAs($this->user)->get('/roadmaps/ai-engineer')->assertNotFound();
});

it('opens the published roadmap from /roadmap', function () {
    $this->actingAs($this->user)->get('/roadmap')->assertRedirect('/roadmaps/ai-engineer');

    $this->roadmap->update(['status' => ContentStatus::Draft]);

    $this->actingAs($this->user)->get('/roadmap')->assertRedirect(route('dashboard'));
});
