<?php

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Track;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, mixed>
 */
function trackForm(Track $track, array $overrides = []): array
{
    return [
        'title' => $track->title,
        'slug' => $track->slug,
        'summary' => $track->summary,
        'why_it_matters' => $track->why_it_matters,
        'description' => $track->description?->toArray(),
        'difficulty' => $track->difficulty->value,
        'estimated_hours' => $track->estimated_hours,
        ...$overrides,
    ];
}

function dependOn(Track $track, Track $prerequisite, string $kind = 'REQUIRED', int $minProgress = 100): void
{
    $track->prerequisites()->attach($prerequisite, ['kind' => $kind, 'min_progress' => $minProgress]);
}

beforeEach(function () {
    $this->roadmap = Roadmap::factory()->published()->create();
    $this->base = Track::factory()->published()->for($this->roadmap)->create(['title' => 'Base', 'slug' => 'base', 'position' => 1]);
    $this->next = Track::factory()->published()->for($this->roadmap)->create(['title' => 'Siguiente', 'slug' => 'siguiente', 'position' => 2]);
    $this->last = Track::factory()->published()->for($this->roadmap)->create(['title' => 'Final', 'slug' => 'final', 'position' => 3]);
    dependOn($this->next, $this->base);
    dependOn($this->last, $this->next, 'RECOMMENDED', 50);
});

it('keeps learners out and lets instructors edit only their own tracks', function () {
    $this->get('/admin/tracks')->assertRedirect(route('login'));
    $this->actingAs(staff(Role::Student))->get('/admin/tracks')->assertForbidden();

    $instructor = staff(Role::Instructor);
    $this->actingAs($instructor)->put("/admin/tracks/{$this->base->id}", trackForm($this->base))->assertForbidden();

    $own = Track::factory()->for($this->roadmap)->create(['created_by' => $instructor->id]);
    $this->actingAs($instructor)
        ->put("/admin/tracks/{$own->id}", trackForm($own, ['title' => 'Mi track']))
        ->assertSessionHasNoErrors();
    expect($own->refresh()->title)->toBe('Mi track');

    // Editing is not publishing.
    $this->actingAs($instructor)->put("/admin/tracks/{$own->id}/status", ['status' => 'PUBLISHED'])->assertForbidden();
});

it('saves the track and learners see it right away', function () {
    $editor = staff(Role::Editor);
    $description = ['version' => 1, 'doc' => ['type' => 'doc', 'content' => [
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Descripción larga del track.']]],
    ]]];

    $this->actingAs($editor)
        ->from("/admin/tracks/{$this->base->id}/edit")
        ->put("/admin/tracks/{$this->base->id}", trackForm($this->base, [
            'title' => 'Fundamentos',
            'slug' => 'fundamentos',
            'description' => $description,
            'estimated_hours' => 12,
        ]))
        ->assertRedirect("/admin/tracks/{$this->base->id}/edit")
        ->assertSessionHasNoErrors();

    $track = $this->base->refresh();
    expect($track->title)->toBe('Fundamentos')
        ->and($track->slug)->toBe('fundamentos')
        ->and($track->description?->toArray())->toEqual($description)
        ->and($track->estimated_hours)->toBe(12)
        ->and($track->updated_by)->toBe($editor->id)
        ->and(AuditLog::where('action', AuditAction::Updated)->where('auditable_id', $track->id)->exists())->toBeTrue();

    // No publishing step: the change is live.
    expect(Track::query()->published()->whereKey($track->id)->value('title'))->toBe('Fundamentos');
});

it('validates slugs and allows an empty description', function () {
    $other = Track::factory()->for(Roadmap::factory())->create(['slug' => 'otro-roadmap']);

    $this->actingAs(staff(Role::Editor))
        ->put("/admin/tracks/{$this->base->id}", trackForm($this->base, ['slug' => 'siguiente']))
        ->assertSessionHasErrors(['slug' => 'El valor de slug ya está en uso.']);

    $this->actingAs(staff(Role::Editor))
        ->put("/admin/tracks/{$this->base->id}", trackForm($this->base, ['slug' => 'Con Tildes á']))
        ->assertSessionHasErrors(['slug' => 'Usa solo minúsculas sin tildes, números y guiones (por ejemplo: git-y-colaboracion).']);

    // Slugs are unique per roadmap, and the description is optional.
    $this->actingAs(staff(Role::Editor))
        ->put("/admin/tracks/{$this->base->id}", trackForm($this->base, ['slug' => $other->slug, 'description' => null]))
        ->assertSessionHasNoErrors();
    expect($this->base->refresh()->description)->toBeNull();
});

it('publishes, unpublishes, archives and restores a track with the right audit action', function () {
    $editor = staff(Role::Editor);
    $draft = Track::factory()->for($this->roadmap)->create();

    $change = fn (string $status) => $this->actingAs($editor)->put("/admin/tracks/{$draft->id}/status", ['status' => $status]);

    $change('PUBLISHED')->assertSessionHasNoErrors();
    expect($draft->refresh()->status)->toBe(ContentStatus::Published)->and($draft->published_at)->not->toBeNull();

    $change('DRAFT')->assertSessionHasNoErrors();
    $change('ARCHIVED')->assertSessionHasNoErrors();

    // An archived track goes back to draft before it can be published.
    $change('PUBLISHED')->assertSessionHasErrors('status');
    $change('DRAFT')->assertSessionHasNoErrors();

    expect(AuditLog::where('auditable_id', $draft->id)->where('auditable_type', 'track')->orderBy('id')->pluck('action')->map->value->all())
        ->toBe(['CREATED', 'PUBLISHED', 'UNPUBLISHED', 'ARCHIVED', 'RESTORED']);
});

it('replaces the prerequisites and logs the change once', function () {
    $this->actingAs(staff(Role::Editor))
        ->put("/admin/tracks/{$this->last->id}/dependencies", ['dependencies' => [
            ['track_id' => $this->base->id, 'kind' => 'REQUIRED', 'min_progress' => 80],
        ]])
        ->assertSessionHasNoErrors();

    expect($this->last->prerequisites()->get()->map(fn (Track $t) => [$t->id, $t->dependency->kind->value, $t->dependency->min_progress])->all())
        ->toBe([[$this->base->id, 'REQUIRED', 80]]);

    $log = AuditLog::where('auditable_id', $this->last->id)->where('action', AuditAction::Updated)->sole();
    expect($log->changes['before']['prerequisites'][0]['track_id'])->toBe($this->next->id)
        ->and($log->changes['after']['prerequisites'][0]['min_progress'])->toBe(80);

    // Saving the same set again writes nothing.
    $this->actingAs(staff(Role::Editor))
        ->put("/admin/tracks/{$this->last->id}/dependencies", ['dependencies' => [
            ['track_id' => $this->base->id, 'kind' => 'REQUIRED', 'min_progress' => 80],
        ]]);
    expect(AuditLog::where('auditable_id', $this->last->id)->where('action', AuditAction::Updated)->count())->toBe(1);
});

it('rejects prerequisites that close a cycle, point at itself or leave the roadmap', function () {
    $foreign = Track::factory()->for(Roadmap::factory())->create();
    $editor = staff(Role::Editor);

    $this->actingAs($editor)
        ->put("/admin/tracks/{$this->base->id}/dependencies", ['dependencies' => [
            ['track_id' => $this->last->id, 'kind' => 'REQUIRED', 'min_progress' => 100],
        ]])
        ->assertSessionHasErrors(['dependencies' => 'Ese cambio crearía un ciclo de prerrequisitos: Base → Final → Siguiente → Base.']);

    $this->actingAs($editor)
        ->put("/admin/tracks/{$this->base->id}/dependencies", ['dependencies' => [
            ['track_id' => $this->base->id, 'kind' => 'REQUIRED', 'min_progress' => 100],
            ['track_id' => $foreign->id, 'kind' => 'REQUIRED', 'min_progress' => 100],
            ['track_id' => $this->next->id, 'kind' => 'MAYBE', 'min_progress' => 0],
        ]])
        ->assertSessionHasErrors([
            'dependencies.0.track_id', 'dependencies.1.track_id', 'dependencies.2.kind', 'dependencies.2.min_progress',
        ]);

    expect($this->base->prerequisites()->count())->toBe(0);
});

it('edits, publishes and reorders modules', function () {
    $editor = staff(Role::Editor);
    $first = Module::factory()->published()->for($this->base)->create(['slug' => 'uno', 'position' => 1]);
    $second = Module::factory()->for($this->base)->create(['slug' => 'dos', 'position' => 2]);

    $this->actingAs($editor)
        ->put("/admin/modules/{$second->id}", ['title' => 'Dos, revisado', 'slug' => 'uno', 'summary' => 'Resumen'])
        ->assertSessionHasErrors('slug');
    $this->actingAs($editor)
        ->put("/admin/modules/{$second->id}", ['title' => 'Dos, revisado', 'slug' => 'dos-revisado', 'summary' => 'Resumen'])
        ->assertSessionHasNoErrors();
    expect($second->refresh()->only('title', 'slug'))->toBe(['title' => 'Dos, revisado', 'slug' => 'dos-revisado']);

    $this->actingAs($editor)->put("/admin/modules/{$second->id}/status", ['status' => 'PUBLISHED'])->assertSessionHasNoErrors();
    expect($second->refresh()->isPublished())->toBeTrue();

    $this->actingAs($editor)
        ->put("/admin/tracks/{$this->base->id}/module-order", ['modules' => [$second->id, $first->id]])
        ->assertSessionHasNoErrors();
    expect($this->base->modules()->pluck('id')->all())->toBe([$second->id, $first->id])
        ->and(AuditLog::where('auditable_id', $this->base->id)->where('auditable_type', 'track')->where('action', AuditAction::Updated)->sole()->changes['after']['module_order'])
        ->toBe([$second->id, $first->id]);

    // The order must list exactly the track's modules.
    $stranger = Module::factory()->for($this->next)->create();
    $this->actingAs($editor)
        ->put("/admin/tracks/{$this->base->id}/module-order", ['modules' => [$first->id, $stranger->id]])
        ->assertSessionHasErrors(['modules' => 'El orden debe incluir exactamente los módulos del track.']);
});

it('hides the lessons of an unpublished module from learners', function () {
    $lesson = publishedLesson(['slug' => 'visible']);
    $learner = User::factory()->create();
    $this->actingAs($learner)->get('/lessons/visible')->assertOk();

    $this->actingAs(staff(Role::Editor))
        ->put("/admin/modules/{$lesson->module_id}/status", ['status' => 'DRAFT'])
        ->assertSessionHasNoErrors();

    $this->actingAs($learner)->get('/lessons/visible')->assertNotFound();
});

it('lists tracks with counts, prerequisites and edit rights', function () {
    Module::factory()->count(2)->for($this->base)->create();

    $this->actingAs(staff(Role::Instructor))
        ->get('/admin/tracks')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tracks/index')
            ->has('roadmaps', 1)
            ->has('roadmaps.0.tracks', 3)
            ->where('roadmaps.0.tracks.0.title', 'Base')
            ->where('roadmaps.0.tracks.0.modules_count', 2)
            ->where('roadmaps.0.tracks.1.prerequisites', ['Base'])
            ->where('roadmaps.0.tracks.0.can_edit', false));
});

it('opens the editor with dependencies, options, modules and the allowed status changes', function () {
    Module::factory()->published()->for($this->next)->create(['title' => 'Módulo A', 'position' => 1]);

    $this->actingAs(staff(Role::Editor))
        ->get("/admin/tracks/{$this->next->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tracks/edit')
            ->where('track.slug', 'siguiente')
            ->where('visible_to_learners', true)
            ->where('status_actions', ['DRAFT', 'ARCHIVED'])
            ->where('dependencies', [['id' => $this->base->id, 'kind' => 'REQUIRED', 'min_progress' => 100]])
            ->where('dependency_options', [
                ['id' => $this->base->id, 'title' => 'Base', 'published' => true],
                ['id' => $this->last->id, 'title' => 'Final', 'published' => true],
            ])
            ->where('modules.0.title', 'Módulo A')
            ->where('modules.0.status_actions', ['DRAFT', 'ARCHIVED']));
});
