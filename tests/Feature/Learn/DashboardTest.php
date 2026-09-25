<?php

use App\Domain\Curriculum\Actions\PublishLesson;
use App\Enums\ContentStatus;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Track;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('sends guests to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('lists the published tracks in order with the lessons a learner can see', function () {
    $roadmap = Roadmap::factory()->published()->create(['title' => 'AI Engineer']);
    Track::factory()->published()->for($roadmap)->create(['title' => 'Python', 'position' => 2]);
    $first = Track::factory()->published()->for($roadmap)->create(['title' => 'Fundamentos', 'position' => 1]);
    Track::factory()->for($roadmap)->create(['title' => 'Borrador', 'position' => 0]);

    $module = Module::factory()->published()->for($first)->create();
    app(PublishLesson::class)->handle(publishableLesson(module: $module));
    app(PublishLesson::class)->handle(publishableLesson(module: $module));
    publishableLesson(module: $module);
    $archived = publishableLesson(module: $module);
    app(PublishLesson::class)->handle($archived);
    $archived->update(['status' => ContentStatus::Archived]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('roadmap.title', 'AI Engineer')
            ->has('tracks', 2)
            ->where('tracks.0.title', 'Fundamentos')
            ->where('tracks.0.lessons_count', 2)
            ->where('tracks.1.title', 'Python')
            ->where('tracks.1.lessons_count', 0)
            ->missing('tracks.0.id'));

    // Two published, one draft and one archived lesson exist in the track.
    expect(Lesson::count())->toBe(4);
});

it('renders an empty dashboard when nothing is published', function () {
    Track::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('roadmap', null)
            ->has('tracks', 0));
});
