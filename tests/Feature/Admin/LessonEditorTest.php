<?php

use App\Enums\AuditAction;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->lesson = publishedLesson(['slug' => 'commits', 'title' => 'Commits', 'position' => 1]);
});

it('keeps learners and guests out of the CMS', function () {
    $this->get('/admin/lessons')->assertRedirect(route('login'));

    $this->actingAs(staff(Role::Student))->get('/admin/lessons')->assertForbidden();
    $this->actingAs(staff(Role::Student))->put('/admin/lessons/commits', lessonForm($this->lesson))->assertForbidden();
});

it('lists every lesson with its status and whether it has unpublished changes', function () {
    $draft = publishableLesson(['slug' => 'borrador', 'title' => 'Borrador', 'position' => 2], $this->lesson->module);
    $this->lesson->update(['title' => 'Commits (en edición)']);

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lessons/index')
            ->has('lessons', 2)
            ->where('lessons.0.slug', 'commits')
            ->where('lessons.0.title', 'Commits (en edición)')
            ->where('lessons.0.status', 'PUBLISHED')
            ->where('lessons.0.version', 1)
            ->where('lessons.0.has_unpublished_changes', true)
            ->where('lessons.0.can_edit', true)
            ->where('lessons.1.slug', $draft->slug)
            ->where('lessons.1.version', null));
});

it('opens the editor with the working copy, the publishing checklist and the versions', function () {
    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/commits/edit')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lessons/edit')
            ->where('lesson.title', 'Commits')
            ->where('lesson.body.version', 1)
            ->where('readiness', [])
            ->where('publication.version', 1)
            ->where('publication.has_unpublished_changes', false)
            ->where('publication.visible_to_learners', true)
            ->has('versions', 1)
            ->where('versions.0.current', true)
            ->where('can.publish', true)
            ->where('content_types', ['CONCEPT', 'TUTORIAL', 'READING', 'VIDEO', 'DOCUMENTATION', 'CHALLENGE']));
});

it('saves the working copy without changing what learners read', function () {
    $editor = staff(Role::Editor);

    $this->actingAs($editor)
        ->from('/admin/lessons/commits/edit')
        ->put('/admin/lessons/commits', lessonForm($this->lesson, [
            'title' => 'Commits, staging y el árbol de trabajo',
            'learning_objectives' => ['  Explicar las tres áreas de Git  ', 'Hacer commits atómicos'],
        ]))
        ->assertRedirect('/admin/lessons/commits/edit')
        ->assertSessionHasNoErrors();

    $lesson = $this->lesson->refresh();
    expect($lesson->title)->toBe('Commits, staging y el árbol de trabajo')
        ->and($lesson->learning_objectives)->toBe(['Explicar las tres áreas de Git', 'Hacer commits atómicos'])
        ->and($lesson->updated_by)->toBe($editor->id)
        ->and($lesson->hasUnpublishedChanges())->toBeTrue()
        ->and(AuditLog::where('action', AuditAction::Updated)->where('user_id', $editor->id)->exists())->toBeTrue();

    // Learners still read version 1.
    $this->actingAs(User::factory()->create())
        ->get('/lessons/commits')
        ->assertInertia(fn (Assert $page) => $page->where('lesson.title', 'Commits'));
});

it('saves rich text exactly as the editor sends it, spaces around marks included', function () {
    $lesson = publishableLesson();
    $paragraph = ['type' => 'paragraph', 'content' => [
        ['type' => 'text', 'text' => 'Una rama es un '],
        ['type' => 'text', 'text' => 'puntero móvil', 'marks' => [['type' => 'bold']]],
        // A bare space between two marks: trimmed, it would become null and fail.
        ['type' => 'text', 'text' => ' '],
        ['type' => 'text', 'text' => 'HEAD', 'marks' => [['type' => 'code']]],
        ['type' => 'text', 'text' => ' indica la rama actual.  '],
    ]];
    $body = ['version' => 1, 'doc' => ['type' => 'doc', 'content' => [...$lesson->body->doc['content'], $paragraph]]];

    $this->actingAs(staff(Role::Editor))
        ->put("/admin/lessons/{$lesson->slug}", lessonForm($lesson, ['body' => $body, 'title' => '  Ramas  ']))
        ->assertSessionHasNoErrors();

    $lesson->refresh();

    // Plain fields are still trimmed; the document is not touched.
    expect($lesson->title)->toBe('Ramas')
        ->and(last($lesson->body->doc['content']))->toEqual($paragraph);
});

it('rejects content outside the RichContent allowlist', function (array $body, string $fragment) {
    $this->actingAs(staff(Role::Editor))
        ->from('/admin/lessons/commits/edit')
        ->put('/admin/lessons/commits', lessonForm($this->lesson, ['body' => $body]))
        ->assertSessionHasErrors('body');

    expect(session('errors')->first('body'))->toContain($fragment)
        ->and($this->lesson->refresh()->hasUnpublishedChanges())->toBeFalse();
})->with([
    'unknown node' => [
        ['version' => 1, 'doc' => ['type' => 'doc', 'content' => [['type' => 'iframe', 'attrs' => ['src' => 'https://example.test']]]]],
        'iframe',
    ],
    'javascript link' => [
        ['version' => 1, 'doc' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [
            ['type' => 'text', 'text' => 'clic', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]],
        ]]]]],
        'esquema de enlace no permitido',
    ],
    'underline mark' => [
        ['version' => 1, 'doc' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [
            ['type' => 'text', 'text' => 'subrayado', 'marks' => [['type' => 'underline']]],
        ]]]]],
        'marca no permitida',
    ],
]);

it('validates the structured fields in Spanish', function () {
    $this->actingAs(staff(Role::Editor))
        ->from('/admin/lessons/commits/edit')
        ->put('/admin/lessons/commits', lessonForm($this->lesson, [
            'title' => '',
            'content_type' => 'LAB',
            'estimated_minutes' => 0,
            'learning_objectives' => ['Uno', ''],
        ]))
        ->assertSessionHasErrors([
            'title' => 'El campo título es obligatorio.',
            'content_type' => 'El valor seleccionado en tipo de contenido no es válido.',
            'estimated_minutes' => 'El campo minutos estimados debe estar entre 1 y 600.',
            'learning_objectives.1' => 'El campo objetivo es obligatorio.',
        ]);
});

it('publishes a new version that learners read right away', function () {
    $editor = staff(Role::Editor);
    $this->lesson->update(['title' => 'Commits bien hechos']);

    $this->actingAs($editor)
        ->from('/admin/lessons/commits/edit')
        ->post('/admin/lessons/commits/publish', ['change_note' => 'Título más claro'])
        ->assertRedirect('/admin/lessons/commits/edit')
        ->assertSessionHasNoErrors();

    $version = $this->lesson->refresh()->publishedVersion;
    expect($version->version)->toBe(2)
        ->and($version->change_note)->toBe('Título más claro')
        ->and($version->published_by)->toBe($editor->id);

    $this->actingAs(User::factory()->create())
        ->get('/lessons/commits')
        ->assertInertia(fn (Assert $page) => $page->where('lesson.title', 'Commits bien hechos')->where('lesson.version', 2));
});

it('does not create a version when nothing changed', function () {
    $this->actingAs(staff(Role::Editor))->post('/admin/lessons/commits/publish');

    expect($this->lesson->versions()->count())->toBe(1);
});

it('refuses to publish a lesson that breaks the contract', function () {
    $this->lesson->update(['summary' => 'Muy corto']);

    $this->actingAs(staff(Role::Editor))
        ->from('/admin/lessons/commits/edit')
        ->post('/admin/lessons/commits/publish')
        ->assertSessionHasErrors(['publish' => 'Todavía no se puede publicar. Revisa la lista de requisitos.']);

    expect($this->lesson->versions()->count())->toBe(1);

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/commits/edit')
        ->assertInertia(fn (Assert $page) => $page->where('readiness.0.code', 'summary_too_short'));
});

it('lets instructors edit only their own lessons and never publish', function () {
    $instructor = staff(Role::Instructor);
    $own = publishableLesson(['slug' => 'propia', 'position' => 2, 'created_by' => $instructor->id], $this->lesson->module);

    $this->actingAs($instructor)->get('/admin/lessons')->assertInertia(fn (Assert $page) => $page
        ->where('lessons.0.can_edit', false)
        ->where('lessons.1.can_edit', true));

    $this->actingAs($instructor)->get('/admin/lessons/commits/edit')->assertForbidden();
    $this->actingAs($instructor)->put('/admin/lessons/commits', lessonForm($this->lesson))->assertForbidden();

    $this->actingAs($instructor)
        ->from('/admin/lessons/propia/edit')
        ->put('/admin/lessons/propia', lessonForm($own, ['title' => 'Mi lección']))
        ->assertSessionHasNoErrors();
    expect($own->refresh()->title)->toBe('Mi lección');

    $this->actingAs($instructor)->post('/admin/lessons/propia/publish')->assertForbidden();
    $this->actingAs($instructor)
        ->get('/admin/lessons/propia/edit')
        ->assertInertia(fn (Assert $page) => $page->where('can.publish', false));
});
