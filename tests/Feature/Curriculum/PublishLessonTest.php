<?php

use App\Domain\Content\RichContent\RichContent;
use App\Domain\Curriculum\Actions\PublishLesson;
use App\Domain\Curriculum\Publishing\LessonNotReadyToPublish;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Models\AuditLog;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Skill;
use App\Models\Track;
use App\Models\User;
use Database\Factories\LessonFactory;

/**
 * A lesson that satisfies the publishing contract.
 */
function publishableLesson(array $attributes = []): Lesson
{
    $module = Module::factory()->published()->for(Track::factory()->published())->create();
    $paragraph = str_repeat('Git guarda la historia como instantáneas encadenadas por hash. ', 30);

    $lesson = Lesson::factory()->for($module)->create([
        'summary' => str_repeat('Resumen claro de la lección. ', 4),
        'why_it_matters' => str_repeat('Importa porque se usa a diario. ', 4),
        'body' => LessonFactory::body([$paragraph]),
        ...$attributes,
    ]);

    $lesson->body = RichContent::fromDocument([
        'type' => 'doc',
        'content' => [
            ...$lesson->body->doc['content'],
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Práctica']]],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Crea un repositorio y haz tres commits.']]],
        ],
    ]);
    $lesson->save();
    $lesson->skills()->attach(Skill::factory()->create(), ['weight' => 2]);

    return $lesson->refresh();
}

it('snapshots the working copy into version 1', function () {
    $lesson = publishableLesson(['title' => 'Commits']);

    $version = app(PublishLesson::class)->handle($lesson, 'Primera versión');

    $lesson->refresh();
    expect($version->version)->toBe(1)
        ->and($version->title)->toBe('Commits')
        ->and($version->change_note)->toBe('Primera versión')
        ->and($version->content_hash)->toBe($lesson->workingCopyHash())
        ->and($lesson->status)->toBe(ContentStatus::Published)
        ->and($lesson->published_version_id)->toBe($version->id)
        ->and($lesson->hasUnpublishedChanges())->toBeFalse();
});

it('does not create a new version when nothing changed', function () {
    $lesson = publishableLesson();
    $publish = app(PublishLesson::class);

    $first = $publish->handle($lesson);
    $second = $publish->handle($lesson->refresh());

    expect($second->id)->toBe($first->id)
        ->and($lesson->versions()->count())->toBe(1);
});

it('keeps learners on the published version while the working copy changes', function () {
    $lesson = publishableLesson(['title' => 'Título publicado']);
    app(PublishLesson::class)->handle($lesson);

    $lesson->refresh()->update(['title' => 'Borrador nuevo']);

    expect($lesson->hasUnpublishedChanges())->toBeTrue()
        ->and($lesson->refresh()->publishedVersion->title)->toBe('Título publicado');
});

it('creates version 2 when changes are published', function () {
    $lesson = publishableLesson();
    $publish = app(PublishLesson::class);
    $publish->handle($lesson);

    $lesson->refresh()->update(['title' => 'Segunda edición']);
    $version = $publish->handle($lesson->refresh());

    expect($version->version)->toBe(2)
        ->and($lesson->refresh()->publishedVersion->title)->toBe('Segunda edición')
        ->and($lesson->versions()->pluck('version')->all())->toBe([2, 1]);
});

it('refuses to publish a lesson that breaks the pedagogical contract', function () {
    $lesson = publishableLesson(['summary' => 'Muy corto']);
    $lesson->skills()->detach();

    try {
        app(PublishLesson::class)->handle($lesson->refresh());
        $this->fail('Expected LessonNotReadyToPublish');
    } catch (LessonNotReadyToPublish $exception) {
        expect(collect($exception->issues)->pluck('code')->all())->toBe(['summary_too_short', 'missing_skills'])
            ->and($exception->getMessage())->toContain('El resumen debe tener al menos 80 caracteres');
    }

    expect($lesson->refresh()->status)->toBe(ContentStatus::Draft)
        ->and($lesson->versions()->count())->toBe(0);
});

it('requires a published module and track', function () {
    $lesson = publishableLesson();
    $lesson->module->update(['status' => ContentStatus::Draft]);

    app(PublishLesson::class)->handle($lesson->refresh());
})->throws(LessonNotReadyToPublish::class, 'El módulo y el track');

it('audits the publication under the PUBLISHED action with the publisher', function () {
    $editor = User::factory()->create();
    $lesson = publishableLesson();

    $this->actingAs($editor);
    $version = app(PublishLesson::class)->handle($lesson);

    $log = AuditLog::query()->where('auditable_type', 'lesson')->where('auditable_id', $lesson->id)->latest('id')->first();

    expect($log->action)->toBe(AuditAction::Published)
        ->and($log->user_id)->toBe($editor->id)
        ->and($log->changes['after']['published_version_id'])->toBe($version->id)
        ->and($version->published_by)->toBe($editor->id);
});

it('makes versions immutable', function () {
    $version = app(PublishLesson::class)->handle(publishableLesson());

    $version->update(['title' => 'Reescrito']);
})->throws(LogicException::class, 'immutable');

it('shows only published lessons of published parents to learners', function () {
    $visible = publishableLesson();
    app(PublishLesson::class)->handle($visible);

    $draft = publishableLesson();
    $archived = publishableLesson();
    app(PublishLesson::class)->handle($archived);
    $archived->refresh()->update(['status' => ContentStatus::Archived]);

    $hiddenParent = publishableLesson();
    app(PublishLesson::class)->handle($hiddenParent);
    $hiddenParent->module->track->update(['status' => ContentStatus::Draft]);

    expect(Lesson::query()->visibleToLearners()->pluck('id')->all())->toBe([$visible->id])
        ->and($draft->published_version_id)->toBeNull();
});
