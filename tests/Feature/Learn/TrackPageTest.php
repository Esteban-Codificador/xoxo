<?php

use App\Domain\Curriculum\Actions\PublishLesson;
use App\Enums\ContentStatus;
use App\Enums\DependencyKind;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Track;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function publishedTrack(array $attributes = []): Track
{
    return Track::factory()->published()
        ->for(Roadmap::factory()->published()->state(['slug' => 'ai-engineer']))
        ->create(['slug' => 'fundamentos', ...$attributes]);
}

it('sends guests to the login page', function () {
    publishedTrack();

    $this->get('/roadmaps/ai-engineer/tracks/fundamentos')->assertRedirect(route('login'));
});

it('shows the outline in study order with the published titles', function () {
    $track = publishedTrack(['title' => 'Fundamentos']);
    $second = Module::factory()->published()->for($track)->create(['title' => 'Git', 'position' => 2]);
    $first = Module::factory()->published()->for($track)->create(['title' => 'Python', 'position' => 1]);
    $empty = Module::factory()->published()->for($track)->create(['position' => 3]);

    $git = publishableLesson(['slug' => 'commits', 'title' => 'Commits', 'position' => 1, 'estimated_minutes' => 30], $second);
    $python = publishableLesson(['slug' => 'variables', 'title' => 'Variables', 'position' => 1, 'estimated_minutes' => 45], $first);
    publishableLesson(['slug' => 'borrador', 'position' => 2], $first);
    app(PublishLesson::class)->handle($git);
    app(PublishLesson::class)->handle($python);

    // An unpublished edit of the working copy never reaches learners.
    $git->update(['title' => 'Título en edición']);

    $this->actingAs(User::factory()->create())
        ->get('/roadmaps/ai-engineer/tracks/fundamentos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tracks/show')
            ->where('track.title', 'Fundamentos')
            ->where('track.lessons_count', 2)
            ->where('track.total_minutes', 75)
            ->has('modules', 2)
            ->where('modules.0.title', 'Python')
            ->has('modules.0.lessons', 1)
            ->where('modules.0.lessons.0.slug', 'variables')
            ->where('modules.1.lessons.0.title', 'Commits')
            ->missing('modules.0.lessons.0.body'));

    expect($empty->exists)->toBeTrue();
});

it('lists published prerequisites with their kind', function () {
    $track = publishedTrack();
    $python = Track::factory()->published()->for($track->roadmap)->create(['title' => 'Python']);
    $draft = Track::factory()->for($track->roadmap)->create();
    $track->prerequisites()->attach($python, ['kind' => DependencyKind::Required->value, 'min_progress' => 80]);
    $track->prerequisites()->attach($draft, ['kind' => DependencyKind::Recommended->value, 'min_progress' => 80]);

    $this->actingAs(User::factory()->create())
        ->get('/roadmaps/ai-engineer/tracks/fundamentos')
        ->assertInertia(fn (Assert $page) => $page
            ->has('prerequisites', 1)
            ->where('prerequisites.0.title', 'Python')
            ->where('prerequisites.0.kind', 'REQUIRED'));
});

it('answers 404 for tracks a learner cannot see', function (Closure $setup) {
    $setup();

    $this->actingAs(User::factory()->create())
        ->get('/roadmaps/ai-engineer/tracks/fundamentos')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('errors/error'));
})->with([
    'draft track' => [fn () => publishedTrack(['status' => ContentStatus::Draft])],
    'draft roadmap' => [fn () => Track::factory()->published()
        ->for(Roadmap::factory()->state(['slug' => 'ai-engineer']))
        ->create(['slug' => 'fundamentos'])],
    'track of another roadmap' => [fn () => Track::factory()->published()
        ->for(Roadmap::factory()->published())
        ->create(['slug' => 'fundamentos'])
        ->roadmap->update(['slug' => 'otro'])],
    'missing' => [fn () => null],
]);
