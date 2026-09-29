<?php

use App\Domain\Curriculum\Actions\PublishLesson;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Skill;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array{skills: list<array{id: int, weight: int}>, prerequisites: list<array{id: int, kind: string}>, resources: list<int>}
 */
function relationsOf(Lesson $lesson): array
{
    return [
        'skills' => $lesson->skills()->orderBy('skills.id')->get()->map(fn (Skill $s) => ['id' => $s->id, 'weight' => (int) $s->pivot->weight])->all(),
        'prerequisites' => $lesson->prerequisites()->get()->map(fn (Lesson $l) => ['id' => $l->id, 'kind' => $l->dependency->kind->value])->all(),
        'resources' => $lesson->resources()->pluck('resources.id')->all(),
    ];
}

beforeEach(function () {
    $this->first = publishedLesson(['slug' => 'primera', 'title' => 'Primera', 'position' => 1]);
    $this->second = publishedLesson(['slug' => 'segunda', 'title' => 'Segunda', 'position' => 2], $this->first->module);
    $this->third = publishedLesson(['slug' => 'tercera', 'title' => 'Tercera', 'position' => 3], $this->first->module);
    $this->second->prerequisites()->attach($this->first, ['kind' => 'REQUIRED']);
    $this->third->prerequisites()->attach($this->second, ['kind' => 'REQUIRED']);
});

it('opens the relations page with the current relations and the candidates of the same roadmap', function () {
    publishedLesson(['slug' => 'otro-roadmap']);

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/segunda/relations')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lessons/relations')
            ->where('lesson.title', 'Segunda')
            ->has('skills', 1)
            ->where('prerequisites', [['id' => $this->first->id, 'kind' => 'REQUIRED', 'min_progress' => 100]])
            ->has('prerequisite_options', 2)
            ->where('prerequisite_options.0.id', $this->first->id)
            ->where('prerequisite_options.1.id', $this->third->id));
});

it('replaces skills, prerequisites and resources and logs only what changed', function () {
    $git = Skill::factory()->create(['name' => 'Git']);
    [$docs, $book] = ExternalResource::factory()->count(2)->create();
    $this->third->resources()->attach($book, ['position' => 1, 'note' => 'Capítulo 3']);

    $editor = staff(Role::Editor);

    $this->actingAs($editor)
        ->from('/admin/lessons/tercera/relations')
        ->put('/admin/lessons/tercera/relations', [
            'skills' => [['id' => $git->id, 'weight' => 4]],
            'prerequisites' => [
                ['id' => $this->second->id, 'kind' => 'REQUIRED'],
                ['id' => $this->first->id, 'kind' => 'RECOMMENDED'],
            ],
            'resources' => [$docs->id, $book->id],
        ])
        ->assertRedirect('/admin/lessons/tercera/relations')
        ->assertSessionHasNoErrors();

    expect(relationsOf($this->third->refresh()))->toEqual([
        'skills' => [['id' => $git->id, 'weight' => 4]],
        'prerequisites' => [
            ['id' => $this->first->id, 'kind' => 'RECOMMENDED'],
            ['id' => $this->second->id, 'kind' => 'REQUIRED'],
        ],
        'resources' => [$docs->id, $book->id],
    ]);

    // Reordering keeps the note of a resource that stays.
    expect($this->third->resources()->whereKey($book->id)->first()->pivot->note)->toBe('Capítulo 3');

    $log = AuditLog::where('auditable_id', $this->third->id)->where('auditable_type', 'lesson')->where('user_id', $editor->id)->sole();
    expect($log->action)->toBe(AuditAction::Updated);
    expect(array_keys($log->changes['after']))->toEqualCanonicalizing(['skills', 'prerequisites', 'resources']);
});

it('rejects a prerequisite that closes a cycle or points at itself', function () {
    $editor = staff(Role::Editor);
    $keep = relationsOf($this->first);

    $this->actingAs($editor)
        ->put('/admin/lessons/primera/relations', [...$keep, 'prerequisites' => [['id' => $this->third->id, 'kind' => 'RECOMMENDED']]])
        ->assertSessionHasErrors(['prerequisites' => 'Ese cambio crearía un ciclo de prerrequisitos: Primera → Tercera → Segunda → Primera.']);

    $this->actingAs($editor)
        ->put('/admin/lessons/primera/relations', [...$keep, 'prerequisites' => [
            ['id' => $this->first->id, 'kind' => 'REQUIRED'],
            ['id' => publishedLesson()->id, 'kind' => 'REQUIRED'],
        ]])
        ->assertSessionHasErrors(['prerequisites.0.id', 'prerequisites.1.id']);

    expect($this->first->prerequisites()->count())->toBe(0);
});

it('lets instructors change relations only of their own lessons', function () {
    $instructor = staff(Role::Instructor);

    $this->actingAs($instructor)->get('/admin/lessons/segunda/relations')->assertForbidden();
    $this->actingAs($instructor)->put('/admin/lessons/segunda/relations', relationsOf($this->second))->assertForbidden();
});

it('renames the slug and moves the editor to the new URL', function () {
    $this->actingAs(staff(Role::Editor))
        ->put('/admin/lessons/segunda', lessonForm($this->second, ['slug' => 'segunda-leccion']))
        ->assertRedirect('/admin/lessons/segunda-leccion/edit');

    $learner = User::factory()->create();
    $this->actingAs($learner)->get('/lessons/segunda')->assertNotFound();
    $this->actingAs($learner)->get('/lessons/segunda-leccion')->assertOk();

    $this->actingAs(staff(Role::Editor))
        ->put('/admin/lessons/segunda-leccion', lessonForm($this->second->refresh(), ['slug' => 'primera']))
        ->assertSessionHasErrors('slug');
});

it('archives a lesson out of sight and restores its published version', function () {
    $editor = staff(Role::Editor);
    $learner = User::factory()->create();

    $this->actingAs($editor)->put('/admin/lessons/segunda/status', ['status' => 'ARCHIVED'])->assertSessionHasNoErrors();
    $this->actingAs($learner)->get('/lessons/segunda')->assertNotFound();

    // Archived lessons are restored before they can be published again.
    $this->actingAs($editor)->post('/admin/lessons/segunda/publish')
        ->assertSessionHasErrors(['publish' => 'La lección está archivada: restáurala antes de publicarla.']);

    $this->actingAs($editor)->put('/admin/lessons/segunda/status', ['status' => 'DRAFT'])->assertSessionHasNoErrors();
    $this->actingAs($learner)->get('/lessons/segunda')->assertOk();

    // Publishing unchanged content reuses version 1.
    $this->actingAs($editor)->post('/admin/lessons/segunda/publish')->assertSessionHasNoErrors();
    expect($this->second->refresh()->status)->toBe(ContentStatus::Published)
        ->and($this->second->versions()->count())->toBe(1);

    expect(AuditLog::where('auditable_id', $this->second->id)->where('auditable_type', 'lesson')->orderBy('id')->pluck('action')->map->value->all())
        ->toContain('ARCHIVED', 'RESTORED');
});

it('never publishes a lesson through the status endpoint and needs the archive permission', function () {
    $this->actingAs(staff(Role::Editor))
        ->put('/admin/lessons/segunda/status', ['status' => 'DRAFT'])
        ->assertSessionHasErrors('status');

    $this->second->update(['status' => ContentStatus::Archived]);
    $this->actingAs(staff(Role::Editor))
        ->put('/admin/lessons/segunda/status', ['status' => 'PUBLISHED'])
        ->assertSessionHasErrors('status');

    $own = publishableLesson(['created_by' => ($instructor = staff(Role::Instructor))->id], Module::find($this->first->module_id));
    $this->actingAs($instructor)->put("/admin/lessons/{$own->slug}/status", ['status' => 'ARCHIVED'])->assertForbidden();
});

it('offers archive on the editor and the version a publish would make live', function () {
    $this->second->update(['status' => ContentStatus::Draft]);

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/segunda/edit')
        ->assertInertia(fn (Assert $page) => $page
            ->where('status_actions', ['ARCHIVED'])
            ->where('publication.live', false)
            ->where('publication.has_unpublished_changes', false)
            ->where('publication.next_version', 1));

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/tercera/edit')
        ->assertInertia(fn (Assert $page) => $page->where('publication.live', true)->where('publication.next_version', null));

    app(PublishLesson::class)->handle($this->third->refresh());
    $this->third->update(['title' => 'Tercera, revisada']);

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/tercera/edit')
        ->assertInertia(fn (Assert $page) => $page
            ->where('publication.live', true)
            ->where('publication.next_version', 2));
});
