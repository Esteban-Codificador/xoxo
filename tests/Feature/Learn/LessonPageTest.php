<?php

use App\Domain\Curriculum\Actions\PublishLesson;
use App\Enums\ContentStatus;
use App\Enums\DependencyKind;
use App\Enums\LinkStatus;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Skill;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function publishedLesson(array $attributes = [], ?Module $module = null): Lesson
{
    $lesson = publishableLesson($attributes, $module);
    app(PublishLesson::class)->handle($lesson);

    return $lesson->refresh();
}

it('sends guests to the login page', function () {
    publishedLesson(['slug' => 'commits']);

    $this->get('/lessons/commits')->assertRedirect(route('login'));
});

it('shows the published version, never the working copy', function () {
    $lesson = publishedLesson(['slug' => 'commits', 'title' => 'Commits', 'estimated_minutes' => 35]);
    $lesson->update(['title' => 'Título en edición', 'summary' => str_repeat('Borrador sin publicar. ', 5)]);

    $this->actingAs(User::factory()->create())
        ->get('/lessons/commits')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('lessons/show')
            ->where('lesson.title', 'Commits')
            ->where('lesson.version', 1)
            ->where('lesson.estimated_minutes', 35)
            ->where('lesson.body.version', 1)
            ->where('lesson.body.doc.type', 'doc')
            ->where('track.title', $lesson->module->track->title)
            ->where('module.title', $lesson->module->title)
            ->where('roadmap.slug', $lesson->module->track->roadmap->slug));
});

it('links the previous and next lesson across modules in study order', function () {
    $first = publishedLesson(['slug' => 'a', 'position' => 1]);
    $track = $first->module->track;
    $laterModule = Module::factory()->published()->for($track)->create(['position' => $first->module->position + 1]);
    $second = publishedLesson(['slug' => 'b', 'position' => 2], $first->module);
    publishableLesson(['slug' => 'borrador', 'position' => 3], $first->module);
    $third = publishedLesson(['slug' => 'c', 'position' => 1], $laterModule);

    $user = User::factory()->create();

    $this->actingAs($user)->get('/lessons/a')->assertInertia(fn (Assert $page) => $page
        ->where('previous', null)
        ->where('next.slug', 'b'));

    $this->actingAs($user)->get('/lessons/b')->assertInertia(fn (Assert $page) => $page
        ->where('previous.slug', 'a')
        ->where('next.slug', 'c'));

    $this->actingAs($user)->get('/lessons/c')->assertInertia(fn (Assert $page) => $page
        ->where('previous.slug', 'b')
        ->where('next', null));

    expect([$second->exists, $third->exists])->toBe([true, true]);
});

it('shows only visible prerequisites and published skills and resources', function () {
    $lesson = publishedLesson(['slug' => 'ramas']);
    $visible = publishedLesson(['slug' => 'commits', 'title' => 'Commits'], $lesson->module);
    $draft = publishableLesson(['slug' => 'borrador'], $lesson->module);
    $lesson->prerequisites()->attach($visible, ['kind' => DependencyKind::Required->value]);
    $lesson->prerequisites()->attach($draft, ['kind' => DependencyKind::Recommended->value]);

    $lesson->skills()->detach();
    $lesson->skills()->attach(Skill::factory()->create(['name' => 'Git']), ['weight' => 3]);
    $lesson->skills()->attach(Skill::factory()->create(['name' => 'Bash']), ['weight' => 1]);
    $lesson->skills()->attach(Skill::factory()->create(['name' => 'Oculta', 'status' => ContentStatus::Draft, 'published_at' => null]), ['weight' => 5]);

    $lesson->resources()->attach(ExternalResource::factory()->create([
        'title' => 'Pro Git',
        'link_status' => LinkStatus::Broken,
    ]), ['position' => 1, 'note' => 'Capítulo 3']);
    $lesson->resources()->attach(ExternalResource::factory()->create(['title' => 'Borrador', 'status' => ContentStatus::Draft, 'published_at' => null]), ['position' => 2]);

    $this->actingAs(User::factory()->create())
        ->get('/lessons/ramas')
        ->assertInertia(fn (Assert $page) => $page
            ->has('prerequisites', 1)
            ->where('prerequisites.0', ['slug' => 'commits', 'title' => 'Commits', 'kind' => 'REQUIRED'])
            ->where('skills', [
                ['slug' => Skill::firstWhere('name', 'Git')->slug, 'name' => 'Git'],
                ['slug' => Skill::firstWhere('name', 'Bash')->slug, 'name' => 'Bash'],
            ])
            ->has('resources', 1)
            ->where('resources.0.title', 'Pro Git')
            ->where('resources.0.note', 'Capítulo 3')
            ->where('resources.0.link_status', 'BROKEN'));
});

it('answers 404 for lessons a learner cannot see', function (Closure $setup) {
    $setup();

    $this->actingAs(User::factory()->create())
        ->get('/lessons/commits')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('errors/error'));
})->with([
    'never published' => [fn () => publishableLesson(['slug' => 'commits'])],
    'archived' => [fn () => publishedLesson(['slug' => 'commits'])->update(['status' => ContentStatus::Archived])],
    'draft module' => [fn () => publishedLesson(['slug' => 'commits'])->module->update(['status' => ContentStatus::Draft])],
    'draft track' => [fn () => publishedLesson(['slug' => 'commits'])->module->track->update(['status' => ContentStatus::Draft])],
    'draft roadmap' => [fn () => publishedLesson(['slug' => 'commits'])->module->track->roadmap->update(['status' => ContentStatus::Draft])],
    'missing' => [fn () => null],
]);

it('links dashboard cards to the track page', function () {
    $lesson = publishedLesson(['slug' => 'commits']);
    $track = $lesson->module->track;

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('roadmap.slug', $track->roadmap->slug)
            ->where('tracks.0.slug', $track->slug));

    $this->get(route('tracks.show', [$track->roadmap->slug, $track->slug]))->assertOk();
});
