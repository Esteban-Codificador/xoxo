<?php

use App\Domain\Content\RichContent\RichContent;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\DependencyKind;
use App\Enums\Role;
use App\Enums\UnlockPolicy;
use App\Models\AuditLog;
use App\Models\Roadmap;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Editing the roadmap itself (step 6 of the CMS): what learners read above
| the map, its unlock policy and its status. Not versioned: learners see
| the change on their next page.
*/

beforeEach(function () {
    $this->lesson = publishedLesson(['slug' => 'commits', 'title' => 'Commits']);
    $this->roadmap = $this->lesson->module->track->roadmap;
    $this->roadmap->update(['slug' => 'ai-engineer', 'title' => 'AI Engineer', 'summary' => 'Ruta completa.', 'unlock_policy' => UnlockPolicy::Advisory]);
});

/**
 * @return array<string, mixed>
 */
function roadmapForm(Roadmap $roadmap, array $overrides = []): array
{
    return [
        'title' => $roadmap->title,
        'slug' => $roadmap->slug,
        'summary' => $roadmap->summary,
        'description' => $roadmap->description?->toArray(),
        'unlock_policy' => $roadmap->unlock_policy->value,
        ...$overrides,
    ];
}

it('opens the editor with the fields, the track counts and the allowed status changes', function () {
    $this->actingAs(staff(Role::Editor))->get("/admin/roadmaps/{$this->roadmap->id}/edit")
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/roadmaps/edit')
            ->where('roadmap.slug', 'ai-engineer')
            ->where('roadmap.unlock_policy', 'ADVISORY')
            ->where('roadmap.status', 'PUBLISHED')
            ->where('roadmap.tracks', 1)
            ->where('roadmap.published_tracks', 1)
            ->where('unlock_policies', ['ADVISORY', 'STRICT'])
            ->where('status_actions', ['DRAFT', 'ARCHIVED']));
});

it('saves the roadmap, audits the change and learners read it on their next page', function () {
    $editor = staff(Role::Editor);
    $description = RichContent::fromDocument(['type' => 'doc', 'content' => [
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'De los fundamentos a producción.']]],
    ]]);

    $this->actingAs($editor)
        ->put("/admin/roadmaps/{$this->roadmap->id}", roadmapForm($this->roadmap, [
            'title' => 'Ingeniería de IA',
            'summary' => 'Del primer script a sistemas de IA en producción.',
            'description' => $description->toArray(),
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Roadmap guardado. Los estudiantes ven el cambio de inmediato.');

    $log = AuditLog::query()->where('auditable_type', 'roadmap')->latest('id')->first();
    expect($log->action)->toBe(AuditAction::Updated)
        ->and($log->user_id)->toBe($editor->id)
        ->and($log->changes['after'])->toHaveKeys(['title', 'summary', 'description'])
        ->and($log->changes['after'])->not->toHaveKeys(['slug', 'unlock_policy']);

    $this->actingAs(staff(Role::Student))->get('/roadmaps/ai-engineer')
        ->assertInertia(fn (Assert $page) => $page
            ->where('roadmap.title', 'Ingeniería de IA')
            ->where('roadmap.summary', 'Del primer script a sistemas de IA en producción.')
            ->where('roadmap.description.doc.content.0.content.0.text', 'De los fundamentos a producción.'));

    // An empty description is saved as none.
    $this->actingAs($editor)->put("/admin/roadmaps/{$this->roadmap->id}", roadmapForm($this->roadmap->refresh(), ['description' => null]))
        ->assertSessionHasNoErrors();
    expect($this->roadmap->refresh()->description)->toBeNull();
});

it('applies a new unlock policy to learners right away', function () {
    $prerequisite = publishedLesson(['slug' => 'intro'], $this->lesson->module);
    $this->lesson->prerequisites()->attach($prerequisite, ['kind' => DependencyKind::Required->value]);
    $learner = staff(Role::Student);

    $this->actingAs($learner)->get('/lessons/commits')->assertInertia(fn (Assert $page) => $page->where('progress.can_progress', true));

    $this->actingAs(staff(Role::Editor))
        ->put("/admin/roadmaps/{$this->roadmap->id}", roadmapForm($this->roadmap, ['unlock_policy' => 'STRICT']))
        ->assertSessionHasNoErrors();

    $this->actingAs($learner)->get('/lessons/commits')->assertInertia(fn (Assert $page) => $page
        ->where('progress.policy', 'STRICT')
        ->where('progress.can_progress', false));
    $this->actingAs($learner)->post('/lessons/commits/complete')->assertForbidden();
});

it('renames the slug, which moves the learner URL', function () {
    $this->actingAs(staff(Role::Editor))
        ->put("/admin/roadmaps/{$this->roadmap->id}", roadmapForm($this->roadmap, ['slug' => 'ingenieria-ia']))
        ->assertSessionHasNoErrors();

    $learner = staff(Role::Student);
    $this->actingAs($learner)->get('/roadmaps/ingenieria-ia')->assertOk();
    $this->actingAs($learner)->get('/roadmaps/ai-engineer')->assertNotFound();
});

it('validates the roadmap fields', function () {
    Roadmap::factory()->create(['slug' => 'otro']);

    $this->actingAs(staff(Role::Editor))
        ->put("/admin/roadmaps/{$this->roadmap->id}", roadmapForm($this->roadmap, [
            'title' => '', 'slug' => 'otro', 'summary' => str_repeat('x', 2001), 'unlock_policy' => 'LAX',
            'description' => ['version' => 1, 'doc' => ['type' => 'doc', 'content' => [['type' => 'iframe']]]],
        ]))
        ->assertSessionHasErrors(['title', 'slug', 'summary', 'unlock_policy', 'description']);

    $this->actingAs(staff(Role::Editor))
        ->put("/admin/roadmaps/{$this->roadmap->id}", roadmapForm($this->roadmap, ['slug' => 'Con Espacios']))
        ->assertSessionHasErrors('slug');
});

it('unpublishes the roadmap, hiding all its content, and publishes it back', function () {
    $editor = staff(Role::Editor);
    $learner = staff(Role::Student);

    $this->actingAs($editor)->put("/admin/roadmaps/{$this->roadmap->id}/status", ['status' => 'DRAFT'])
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'El roadmap quedó en borrador: los estudiantes no ven ninguno de sus tracks.');

    expect($this->roadmap->refresh()->status)->toBe(ContentStatus::Draft)
        ->and(AuditLog::query()->where('auditable_type', 'roadmap')->latest('id')->first()->action)->toBe(AuditAction::Unpublished);
    $this->actingAs($learner)->get('/roadmaps/ai-engineer')->assertNotFound();
    $this->actingAs($learner)->get('/lessons/commits')->assertNotFound();

    // A transition that does not exist is a validation error.
    $this->actingAs($editor)->put("/admin/roadmaps/{$this->roadmap->id}/status", ['status' => 'DRAFT'])->assertSessionHasErrors('status');

    $this->actingAs($editor)->put("/admin/roadmaps/{$this->roadmap->id}/status", ['status' => 'PUBLISHED'])->assertSessionHasNoErrors();
    expect(AuditLog::query()->where('auditable_type', 'roadmap')->latest('id')->first()->action)->toBe(AuditAction::Published);
    $this->actingAs($learner)->get('/lessons/commits')->assertOk();
});

it('keeps the roadmap to who edits any content', function () {
    $this->get("/admin/roadmaps/{$this->roadmap->id}/edit")->assertRedirect('/login');
    $instructor = staff(Role::Instructor);

    $this->actingAs($instructor)->get("/admin/roadmaps/{$this->roadmap->id}/edit")->assertForbidden();
    $this->actingAs($instructor)->put("/admin/roadmaps/{$this->roadmap->id}", roadmapForm($this->roadmap, ['title' => 'x']))->assertForbidden();
    $this->actingAs($instructor)->put("/admin/roadmaps/{$this->roadmap->id}/status", ['status' => 'DRAFT'])->assertForbidden();
    $this->actingAs(staff(Role::Student))->get("/admin/roadmaps/{$this->roadmap->id}/edit")->assertForbidden();

    expect($this->roadmap->refresh()->title)->toBe('AI Engineer');

    // The track list offers the button only to who can use it.
    $this->actingAs($instructor)->get('/admin/tracks')
        ->assertInertia(fn (Assert $page) => $page->where('roadmaps.0.can_edit', false));
    $this->actingAs(staff(Role::Editor))->get('/admin/tracks')
        ->assertInertia(fn (Assert $page) => $page
            ->where('roadmaps.0.id', $this->roadmap->id)
            ->where('roadmaps.0.status', 'PUBLISHED')
            ->where('roadmaps.0.can_edit', true));
});

it('links roadmap entries of the audit log to the roadmap editor', function () {
    $admin = staff(Role::Admin);
    $this->actingAs($admin);
    $this->roadmap->update(['title' => 'AI Engineering']);

    $this->get('/admin/audit?entity=roadmap')
        ->assertInertia(fn (Assert $page) => $page->where('entries.0.href', "/admin/roadmaps/{$this->roadmap->id}/edit"));
});
