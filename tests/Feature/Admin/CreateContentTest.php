<?php

use App\Domain\Curriculum\Publishing\LessonTemplate;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Track;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Creating curriculum in the CMS (step 4d): tracks, modules and lessons are
| born as drafts at the end of their parent, owned by whoever creates them.
*/

beforeEach(function () {
    $this->roadmap = Roadmap::factory()->published()->create(['slug' => 'ai-engineer']);
    $this->track = Track::factory()->for($this->roadmap)->create(['slug' => 'fundamentos', 'position' => 3]);
    $this->module = Module::factory()->for($this->track)->create(['slug' => 'git', 'position' => 4]);
});

/**
 * @return array<string, mixed>
 */
function newTrackForm(array $overrides = []): array
{
    return [
        'roadmap_id' => Roadmap::firstWhere('slug', 'ai-engineer')->id,
        'title' => 'Python para IA',
        'slug' => 'python-para-ia',
        'summary' => 'Python como lenguaje de trabajo del roadmap.',
        'why_it_matters' => 'Todo el ecosistema de IA se escribe en Python.',
        'difficulty' => 'BEGINNER',
        'estimated_hours' => 20,
        ...$overrides,
    ];
}

/**
 * @return array<string, mixed>
 */
function newLessonForm(array $overrides = []): array
{
    return [
        'module_id' => Module::firstWhere('slug', 'git')->id,
        'title' => 'Git stash',
        'slug' => 'git-stash',
        'summary' => 'Guardar cambios a medias sin hacer commit.',
        'why_it_matters' => 'Cambiar de tarea sin perder trabajo.',
        'content_type' => 'TUTORIAL',
        'difficulty' => 'BEGINNER',
        'estimated_minutes' => 25,
        ...$overrides,
    ];
}

it('creates a track as a draft at the end of its roadmap', function () {
    $editor = staff(Role::Editor);

    $this->actingAs($editor)->post('/admin/tracks', newTrackForm())->assertSessionHasNoErrors();

    $track = Track::firstWhere('slug', 'python-para-ia');
    expect($track->status)->toBe(ContentStatus::Draft)
        ->and($track->position)->toBe(4)
        ->and($track->created_by)->toBe($editor->id)
        ->and($track->roadmap_id)->toBe($this->roadmap->id)
        ->and(AuditLog::query()->where('action', AuditAction::Created)->where('auditable_type', 'track')->where('auditable_id', $track->id)->exists())->toBeTrue();

    $this->actingAs($editor)->post('/admin/tracks', newTrackForm())->assertSessionHasErrors('slug');
    $this->actingAs($editor)->get("/admin/tracks/{$track->id}/edit")->assertOk();
});

it('adds modules at the end of a track the user can edit', function () {
    $editor = staff(Role::Editor);

    $this->actingAs($editor)
        ->post("/admin/tracks/{$this->track->id}/modules", ['title' => 'Colaboración', 'slug' => 'colaboracion', 'summary' => 'Trabajo en equipo con Git.'])
        ->assertSessionHasNoErrors();

    $module = Module::firstWhere('slug', 'colaboracion');
    expect($module->track_id)->toBe($this->track->id)
        ->and($module->position)->toBe(5)
        ->and($module->status)->toBe(ContentStatus::Draft);

    // Unique inside the track; the same slug in another track is fine.
    $this->actingAs($editor)
        ->post("/admin/tracks/{$this->track->id}/modules", ['title' => 'Otro', 'slug' => 'colaboracion', 'summary' => 'x'])
        ->assertSessionHasErrors('slug');
});

it('creates a lesson with the body template, owned by its author', function () {
    $instructor = staff(Role::Instructor);

    $this->actingAs($instructor)->post('/admin/lessons', newLessonForm())
        ->assertRedirect('/admin/lessons/git-stash/edit');

    $lesson = Lesson::firstWhere('slug', 'git-stash');
    expect($lesson->status)->toBe(ContentStatus::Draft)
        ->and($lesson->module_id)->toBe($this->module->id)
        ->and($lesson->created_by)->toBe($instructor->id)
        ->and($lesson->body->headings(2))->toBe(LessonTemplate::SECTIONS)
        ->and($lesson->published_version_id)->toBeNull();

    // Its author edits it; learners never see a draft.
    $this->actingAs($instructor)->get('/admin/lessons/git-stash/edit')->assertOk();
    expect(Lesson::query()->visibleToLearners()->whereKey($lesson->id)->exists())->toBeFalse();

    // Placed after the last lesson of the module.
    $this->actingAs($instructor)->post('/admin/lessons', newLessonForm(['slug' => 'git-reflog', 'title' => 'Reflog']));
    expect(Lesson::firstWhere('slug', 'git-reflog')->position)->toBe($lesson->position + 1);
});

it('validates new lessons', function () {
    $this->actingAs(staff(Role::Editor))
        ->post('/admin/lessons', newLessonForm(['slug' => 'Con Espacios', 'content_type' => 'PROJECT', 'module_id' => 999_999, 'summary' => '']))
        ->assertSessionHasErrors(['slug', 'content_type', 'module_id', 'summary']);
});

it('offers the modules grouped by track and preselects one', function () {
    $this->actingAs(staff(Role::Editor))
        ->get("/admin/lessons/create?module={$this->module->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lessons/create')
            ->where('module_id', $this->module->id)
            ->where('tracks.0.title', $this->track->title)
            ->where('tracks.0.modules.0.id', $this->module->id));
});

it('lets instructors create tracks and lessons but not modules in tracks they do not own', function () {
    $instructor = staff(Role::Instructor);

    $this->actingAs($instructor)->get('/admin/tracks/create')->assertOk();
    $this->actingAs($instructor)->post('/admin/tracks', newTrackForm())->assertSessionHasNoErrors();
    $own = Track::firstWhere('slug', 'python-para-ia');

    $this->actingAs($instructor)
        ->post("/admin/tracks/{$own->id}/modules", ['title' => 'Sintaxis', 'slug' => 'sintaxis', 'summary' => 'Lo básico.'])
        ->assertSessionHasNoErrors();
    $this->actingAs($instructor)
        ->post("/admin/tracks/{$this->track->id}/modules", ['title' => 'Ajeno', 'slug' => 'ajeno', 'summary' => 'x'])
        ->assertForbidden();

    $this->actingAs($instructor)->get("/admin/tracks/{$this->track->id}/edit")->assertForbidden();
    $this->actingAs($instructor)->get("/admin/tracks/{$own->id}/edit")
        ->assertInertia(fn (Assert $page) => $page->where('can.create_module', true)->where('can.create_lesson', true));

    $student = staff(Role::Student);
    $this->actingAs($student)->get('/admin/lessons/create')->assertForbidden();
    $this->actingAs($student)->post('/admin/lessons', newLessonForm())->assertForbidden();
});

it('tells the lists whether the user can create', function () {
    $this->actingAs(staff(Role::Editor))->get('/admin/tracks')->assertInertia(fn (Assert $page) => $page->where('can.create', true));
    $this->actingAs(staff(Role::Editor))->get('/admin/lessons')->assertInertia(fn (Assert $page) => $page->where('can.create', true));
});
