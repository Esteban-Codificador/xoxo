<?php

use App\Domain\Learning\Actions\CompleteLesson;
use App\Enums\ContentStatus;
use App\Enums\DependencyKind;
use App\Models\ExternalResource;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Skill;
use App\Models\Track;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
| The learner's skills (master spec §38): the list with progress, one
| skill's page and the summary on the dashboard.
*/

beforeEach(function () {
    $roadmap = Roadmap::factory()->published()->create(['slug' => 'ai-engineer']);
    $module = Module::factory()->published()->for(Track::factory()->published()->for($roadmap)->create(['title' => 'Fundamentos']))->create();
    $this->commits = publishedLesson(['slug' => 'commits', 'title' => 'Commits', 'position' => 1], $module);
    $this->ramas = publishedLesson(['slug' => 'ramas', 'title' => 'Ramas', 'position' => 2], $module);
    Skill::query()->delete();

    $this->git = Skill::factory()->create(['slug' => 'git', 'name' => 'Git', 'description' => 'Control de versiones con Git.']);
    $this->python = Skill::factory()->create(['slug' => 'python', 'name' => 'Python']);
    Skill::factory()->create(['slug' => 'borrador', 'name' => 'Borrador', 'status' => ContentStatus::Draft]);
    $this->commits->skills()->attach($this->git, ['weight' => 2]);
    $this->ramas->skills()->attach($this->git, ['weight' => 2]);
    $this->git->prerequisites()->attach($this->python, ['kind' => DependencyKind::Recommended->value, 'min_progress' => 50]);

    $this->user = User::factory()->create();
});

it('keeps guests out', function () {
    $this->get('/skills')->assertRedirect('/login');
    $this->get('/skills/git')->assertRedirect('/login');
});

it('lists published skills with the learner progress', function () {
    app(CompleteLesson::class)->handle($this->user, $this->commits);

    $this->actingAs($this->user)->get('/skills')
        ->assertInertia(fn (Assert $page) => $page
            ->component('skills/index')
            ->has('skills', 2)
            ->where('skills.0.slug', 'git')
            ->where('skills.0.progress.state', 'IN_PROGRESS')
            ->where('skills.0.progress.progress', 50)
            ->where('skills.0.progress.completed', 1)
            ->where('skills.0.progress.total', 2)
            ->where('skills.1.slug', 'python')
            ->where('skills.1.progress.total', 0)
            ->where('summary', ['completed' => 0, 'total' => 2]));
});

it('shows a skill with its lessons, prerequisites, what it leads to and its resources', function () {
    app(CompleteLesson::class)->handle($this->user, $this->commits);
    $resource = ExternalResource::factory()->create(['title' => 'Pro Git']);
    $draftResource = ExternalResource::factory()->create(['status' => ContentStatus::Draft]);
    $this->git->resources()->attach([$resource->id => ['position' => 1], $draftResource->id => ['position' => 2]]);

    $this->actingAs($this->user)->get('/skills/git')
        ->assertInertia(fn (Assert $page) => $page
            ->component('skills/show')
            ->where('roadmap.slug', 'ai-engineer')
            ->where('skill.name', 'Git')
            ->where('skill.description', 'Control de versiones con Git.')
            ->where('progress.progress', 50)
            ->where('lessons', [
                ['slug' => 'commits', 'title' => 'Commits', 'track' => 'Fundamentos', 'state' => 'COMPLETED'],
                ['slug' => 'ramas', 'title' => 'Ramas', 'track' => 'Fundamentos', 'state' => 'AVAILABLE'],
            ])
            ->where('prerequisites.0.slug', 'python')
            ->where('prerequisites.0.kind', 'RECOMMENDED')
            ->where('prerequisites.0.progress', 0)
            ->where('enables', [])
            ->has('resources', 1)
            ->where('resources.0.title', 'Pro Git'));

    $this->actingAs($this->user)->get('/skills/python')
        ->assertInertia(fn (Assert $page) => $page
            ->where('lessons', [])
            ->where('enables', [['slug' => 'git', 'name' => 'Git', 'state' => 'IN_PROGRESS']]));
});

it('answers 404 for skills learners cannot see', function () {
    $this->actingAs($this->user)->get('/skills/borrador')->assertNotFound();
    $this->actingAs($this->user)->get('/skills/no-existe')->assertNotFound();
});

it('sums up completed skills on the dashboard and links skills from the lesson', function () {
    app(CompleteLesson::class)->handle($this->user, $this->commits);
    app(CompleteLesson::class)->handle($this->user, $this->ramas);

    $this->actingAs($this->user)->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('skills', ['completed' => 1, 'total' => 2]));

    $this->actingAs($this->user)->get('/lessons/commits')
        ->assertInertia(fn (Assert $page) => $page->where('skills', [['slug' => 'git', 'name' => 'Git']]));
});
