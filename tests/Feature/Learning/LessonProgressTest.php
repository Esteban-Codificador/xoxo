<?php

use App\Domain\Learning\Actions\CompleteLesson;
use App\Domain\Learning\Actions\StartLesson;
use App\Enums\ActivityType;
use App\Enums\ContentStatus;
use App\Enums\DependencyKind;
use App\Enums\ProgressStatus;
use App\Enums\UnlockPolicy;
use App\Models\LearningActivity;
use App\Models\LessonProgress;
use App\Models\User;

beforeEach(function () {
    $this->lesson = publishedLesson(['slug' => 'commits']);
    $this->user = User::factory()->create();
});

function progressRow(User $user, $lesson): ?LessonProgress
{
    return LessonProgress::query()->whereBelongsTo($user)->whereBelongsTo($lesson)->first();
}

it('sends guests to the login page', function () {
    $this->post('/lessons/commits/complete')->assertRedirect(route('login'));
});

it('starts a lesson once and only records later visits', function () {
    $this->actingAs($this->user)->from('/lessons/commits')->post('/lessons/commits/start')->assertRedirect('/lessons/commits');
    $this->actingAs($this->user)->from('/lessons/commits')->post('/lessons/commits/start');

    expect(progressRow($this->user, $this->lesson)->status)->toBe(ProgressStatus::InProgress)
        ->and(LearningActivity::where('type', ActivityType::LessonStarted)->count())->toBe(1);
});

it('completes a lesson with the published version it was read in', function () {
    $this->actingAs($this->user)->from('/lessons/commits')->post('/lessons/commits/complete')->assertRedirect('/lessons/commits');

    $row = progressRow($this->user, $this->lesson);

    expect($row->status)->toBe(ProgressStatus::Completed)
        ->and($row->completed_at)->not->toBeNull()
        ->and($row->started_at)->not->toBeNull()
        ->and($row->completed_version_id)->toBe($this->lesson->published_version_id)
        ->and(LearningActivity::where('type', ActivityType::LessonCompleted)->sole()->metadata)
        ->toBe(['version_id' => $this->lesson->published_version_id]);
});

it('treats completing twice as a single completion', function () {
    app(CompleteLesson::class)->handle($this->user, $this->lesson);
    app(CompleteLesson::class)->handle($this->user, $this->lesson);

    expect(LessonProgress::count())->toBe(1)
        ->and(LearningActivity::where('type', ActivityType::LessonCompleted)->count())->toBe(1);
});

it('never moves a completed lesson backwards when it is opened again', function () {
    app(CompleteLesson::class)->handle($this->user, $this->lesson);
    app(StartLesson::class)->handle($this->user, $this->lesson);

    expect(progressRow($this->user, $this->lesson)->status)->toBe(ProgressStatus::Completed);
});

it('uncompletes back to in progress and keeps the feed intact', function () {
    app(CompleteLesson::class)->handle($this->user, $this->lesson);

    $this->actingAs($this->user)->from('/lessons/commits')->delete('/lessons/commits/complete')->assertRedirect('/lessons/commits');

    $row = progressRow($this->user, $this->lesson);
    expect($row->status)->toBe(ProgressStatus::InProgress)
        ->and($row->completed_at)->toBeNull()
        ->and($row->completed_version_id)->toBeNull()
        ->and(LearningActivity::where('type', ActivityType::LessonCompleted)->count())->toBe(1);
});

it('refuses to undo a mastered lesson', function () {
    LessonProgress::create([
        'user_id' => $this->user->id, 'lesson_id' => $this->lesson->id, 'status' => ProgressStatus::Mastered,
        'started_at' => now(), 'completed_at' => now(), 'mastered_at' => now(),
    ]);

    $this->actingAs($this->user)->from('/lessons/commits')->delete('/lessons/commits/complete')
        ->assertSessionHasErrors(['lesson' => 'Una lección dominada se respalda con una evaluación aprobada y no se puede desmarcar.']);

    expect(progressRow($this->user, $this->lesson)->status)->toBe(ProgressStatus::Mastered);
});

it('answers 404 for lessons a learner cannot see', function () {
    $this->lesson->update(['status' => ContentStatus::Archived]);

    $this->actingAs($this->user)->post('/lessons/commits/complete')->assertNotFound();

    expect(LessonProgress::count())->toBe(0);
});

it('lets ADVISORY roadmaps progress on locked lessons and blocks STRICT ones with 403', function () {
    $prerequisite = publishedLesson(['slug' => 'intro'], $this->lesson->module);
    $this->lesson->prerequisites()->attach($prerequisite, ['kind' => DependencyKind::Required->value]);

    $this->actingAs($this->user)->post('/lessons/commits/complete')->assertRedirect();
    expect(progressRow($this->user, $this->lesson)->status)->toBe(ProgressStatus::Completed);

    LessonProgress::query()->delete();
    $this->lesson->module->track->roadmap->update(['unlock_policy' => UnlockPolicy::Strict]);

    $this->actingAs($this->user)->post('/lessons/commits/complete')->assertForbidden();
    $this->actingAs($this->user)->post('/lessons/intro/complete')->assertRedirect();

    expect(progressRow($this->user, $this->lesson))->toBeNull();
});

it('shows the state, blockers and completion on the lesson page', function () {
    $prerequisite = publishedLesson(['slug' => 'intro', 'title' => 'Intro'], $this->lesson->module);
    $this->lesson->prerequisites()->attach($prerequisite, ['kind' => DependencyKind::Required->value]);

    $this->actingAs($this->user)->get('/lessons/commits')->assertInertia(fn ($page) => $page
        ->where('progress.state', 'LOCKED')
        ->where('progress.policy', 'ADVISORY')
        ->where('progress.can_progress', true)
        ->where('progress.completed_at', null)
        ->where('progress.blockers.0.slug', 'intro')
        ->where('prerequisites.0.state', 'AVAILABLE'));

    app(CompleteLesson::class)->handle($this->user, $prerequisite);
    app(CompleteLesson::class)->handle($this->user, $this->lesson);

    $this->actingAs($this->user)->get('/lessons/commits')->assertInertia(fn ($page) => $page
        ->where('progress.state', 'COMPLETED')
        ->where('progress.blockers', [])
        ->where('prerequisites.0.state', 'COMPLETED')
        ->whereType('progress.completed_at', 'string'));
});

it('shows track progress, lesson states and where to continue', function () {
    $second = publishedLesson(['slug' => 'ramas', 'title' => 'Ramas', 'position' => 99], $this->lesson->module);
    $track = $this->lesson->module->track;
    app(CompleteLesson::class)->handle($this->user, $this->lesson);

    $this->actingAs($this->user)
        ->get(route('tracks.show', [$track->roadmap->slug, $track->slug]))
        ->assertInertia(fn ($page) => $page
            ->where('progress.state', 'IN_PROGRESS')
            ->where('progress.progress', 50)
            ->where('progress.completed', 1)
            ->where('policy', 'ADVISORY')
            ->where('continue.slug', 'ramas')
            ->where('modules.0.lessons.0.state', 'COMPLETED')
            ->where('modules.0.lessons.1.state', 'AVAILABLE'));

    $this->actingAs($this->user)->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('tracks.0.progress.progress', 50)
        ->where('continue.slug', 'ramas')
        ->where('continue.state', 'AVAILABLE')
        ->where('all_done', false));

    app(CompleteLesson::class)->handle($this->user, $second);

    $this->actingAs($this->user)->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('tracks.0.progress.state', 'COMPLETED')
        ->where('continue', null)
        ->where('all_done', true));
});
