<?php

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Lesson;
use App\Models\Track;
use App\Models\User;

it('logs creation with the actor and the created values', function () {
    $editor = User::factory()->create();
    $this->actingAs($editor);

    $track = Track::factory()->create(['title' => 'Python']);
    $log = AuditLog::query()->where('auditable_type', 'track')->where('auditable_id', $track->id)->sole();

    expect($log->action)->toBe(AuditAction::Created)
        ->and($log->user_id)->toBe($editor->id)
        ->and($log->changes['after']['title'])->toBe('Python')
        ->and($log->changes)->not->toHaveKey('before');
});

it('logs only the changed fields on update', function () {
    $track = Track::factory()->create(['title' => 'Antes']);

    $track->update(['title' => 'Después']);

    $log = AuditLog::query()->where('auditable_id', $track->id)->where('action', AuditAction::Updated)->sole();

    // jsonb does not keep key order: compare by content.
    expect($log->changes)->toEqual(['before' => ['title' => 'Antes'], 'after' => ['title' => 'Después']]);
});

it('stores large rich fields as a hash instead of their content', function () {
    $lesson = Lesson::factory()->create();

    $created = AuditLog::query()->where('auditable_type', 'lesson')->where('auditable_id', $lesson->id)->sole();

    expect($created->changes['after']['body'])->toHaveKey('sha256')->toHaveCount(1);
});

it('uses the workflow action given by during()', function () {
    $track = Track::factory()->create();

    app(AuditLogger::class)->during(AuditAction::Archived, fn () => $track->update(['status' => 'ARCHIVED']));

    expect(AuditLog::query()->where('auditable_id', $track->id)->latest('id')->first()->action)->toBe(AuditAction::Archived);
});

it('never lets audit rows be edited or deleted', function () {
    $log = AuditLog::query()->create(['action' => AuditAction::Created, 'auditable_type' => 'track', 'auditable_id' => 1]);

    expect(fn () => $log->update(['action' => AuditAction::Deleted]))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);
});
