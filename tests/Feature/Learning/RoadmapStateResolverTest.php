<?php

use App\Domain\Learning\State\RoadmapStateResolver;
use App\Enums\DependencyKind;
use App\Enums\NodeState;
use App\Enums\ProgressStatus;
use App\Enums\UnlockPolicy;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Track;
use App\Models\User;

/**
 * Roadmap with two tracks:
 *   A (position 0): a1 → a2 (a2 REQUIRES a1)
 *   B (position 1): b1; B REQUIRES A at 100 %
 */
beforeEach(function () {
    $this->roadmap = Roadmap::factory()->published()->create(['unlock_policy' => UnlockPolicy::Advisory]);
    $this->trackA = Track::factory()->published()->for($this->roadmap)->create(['slug' => 'a', 'title' => 'Track A', 'position' => 0]);
    $this->trackB = Track::factory()->published()->for($this->roadmap)->create(['slug' => 'b', 'title' => 'Track B', 'position' => 1]);
    $moduleA = Module::factory()->published()->for($this->trackA)->create();
    $moduleB = Module::factory()->published()->for($this->trackB)->create();

    $this->a1 = publishedLesson(['slug' => 'a1', 'title' => 'Lección A1', 'position' => 1], $moduleA);
    $this->a2 = publishedLesson(['slug' => 'a2', 'title' => 'Lección A2', 'position' => 2], $moduleA);
    $this->b1 = publishedLesson(['slug' => 'b1', 'position' => 1], $moduleB);
    $this->a2->prerequisites()->attach($this->a1, ['kind' => DependencyKind::Required->value]);
    $this->trackB->prerequisites()->attach($this->trackA, ['kind' => DependencyKind::Required->value, 'min_progress' => 100]);

    $this->user = User::factory()->create();
});

function progressOf(User $user, Lesson $lesson, ProgressStatus $status, ?string $viewedAt = null): void
{
    LessonProgress::create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => $status,
        'started_at' => now(),
        'completed_at' => $status === ProgressStatus::InProgress ? null : now(),
        'last_viewed_at' => $viewedAt ?? now(),
    ]);
}

function stateFor(User $user, Roadmap $roadmap)
{
    return app(RoadmapStateResolver::class)->resolve($user, $roadmap);
}

it('opens the first lesson and locks what depends on it for a new learner', function () {
    $state = stateFor($this->user, $this->roadmap);

    expect($state->track($this->trackA)->state)->toBe(NodeState::Available)
        ->and($state->track($this->trackA)->progress)->toBe(0)
        ->and($state->lesson($this->a1)->state)->toBe(NodeState::Available)
        ->and($state->lesson($this->a2)->state)->toBe(NodeState::Locked)
        ->and($state->lesson($this->a2)->blockers[0]->toArray())->toMatchArray(['type' => 'lesson', 'slug' => 'a1', 'title' => 'Lección A1'])
        ->and($state->track($this->trackB)->state)->toBe(NodeState::Locked)
        ->and($state->track($this->trackB)->blockers[0]->toArray())->toMatchArray(['type' => 'track', 'slug' => 'a', 'progress' => 0, 'required' => 100])
        ->and($state->lesson($this->b1)->state)->toBe(NodeState::Locked)
        ->and($state->lastViewedInProgress())->toBeNull()
        ->and($state->lastActivityTrackId())->toBeNull()
        ->and($state->trackIds())->toBe([$this->trackA->id, $this->trackB->id]);
});

it('unlocks a lesson when its required prerequisite is completed', function () {
    progressOf($this->user, $this->a1, ProgressStatus::Completed);

    $state = stateFor($this->user, $this->roadmap);

    expect($state->lesson($this->a1)->state)->toBe(NodeState::Completed)
        ->and($state->lesson($this->a2)->state)->toBe(NodeState::Available)
        ->and($state->track($this->trackA)->state)->toBe(NodeState::InProgress)
        ->and($state->track($this->trackA)->progress)->toBe(50)
        ->and($state->track($this->trackB)->state)->toBe(NodeState::Locked)
        ->and($state->track($this->trackB)->blockers[0]->progress)->toBe(50);
});

it('completes a track at 100 % and unlocks the tracks that require it', function () {
    progressOf($this->user, $this->a1, ProgressStatus::Completed);
    progressOf($this->user, $this->a2, ProgressStatus::Completed);

    $state = stateFor($this->user, $this->roadmap);

    expect($state->track($this->trackA)->toArray())->toMatchArray(['state' => 'COMPLETED', 'progress' => 100, 'completed' => 2, 'total' => 2])
        ->and($state->track($this->trackB)->state)->toBe(NodeState::Available)
        ->and($state->lesson($this->b1)->state)->toBe(NodeState::Available);
});

it('never reaches MASTERED for a track without evidence', function () {
    progressOf($this->user, $this->a1, ProgressStatus::Mastered);
    progressOf($this->user, $this->a2, ProgressStatus::Mastered);

    $state = stateFor($this->user, $this->roadmap);

    expect($state->lesson($this->a1)->state)->toBe(NodeState::Mastered)
        ->and($state->track($this->trackA)->state)->toBe(NodeState::Completed);
});

it('keeps a started lesson in progress even if it is locked, and resumes the last one visited', function () {
    progressOf($this->user, $this->b1, ProgressStatus::InProgress, '2026-09-20 10:00:00');
    progressOf($this->user, $this->a1, ProgressStatus::InProgress, '2026-09-21 10:00:00');

    $state = stateFor($this->user, $this->roadmap);

    expect($state->lesson($this->b1)->state)->toBe(NodeState::InProgress)
        ->and($state->lesson($this->b1)->blockers)->not->toBeEmpty()
        ->and($state->lastViewedInProgress()[0])->toBe($this->a1->id)
        ->and($state->lastActivityTrackId())->toBe($this->trackA->id);
});

it('ignores recommended and unpublished prerequisites', function () {
    $this->trackB->prerequisites()->detach();
    $this->trackB->prerequisites()->attach($this->trackA, ['kind' => DependencyKind::Recommended->value, 'min_progress' => 100]);
    $draft = publishableLesson(['slug' => 'borrador'], $this->a1->module);
    $this->b1->prerequisites()->attach($draft, ['kind' => DependencyKind::Required->value]);

    $state = stateFor($this->user, $this->roadmap);

    expect($state->track($this->trackB)->state)->toBe(NodeState::Available)
        ->and($state->lesson($this->b1)->state)->toBe(NodeState::Available)
        ->and($state->hasLesson($draft))->toBeFalse()
        ->and($state->track($this->trackA)->total)->toBe(2);
});

it('rounds progress down so 100 % always means every lesson', function () {
    publishedLesson(['slug' => 'a3', 'position' => 3], $this->a1->module);
    progressOf($this->user, $this->a1, ProgressStatus::Completed);
    progressOf($this->user, $this->a2, ProgressStatus::Completed);

    expect(stateFor($this->user, $this->roadmap)->track($this->trackA)->progress)->toBe(66);
});

it('only counts the learner own progress', function () {
    progressOf(User::factory()->create(), $this->a1, ProgressStatus::Completed);

    expect(stateFor($this->user, $this->roadmap)->lesson($this->a2)->state)->toBe(NodeState::Locked);
});

it('lets ADVISORY progress on locked lessons and STRICT only on unlocked ones', function () {
    expect(stateFor($this->user, $this->roadmap)->canProgress($this->a2))->toBeTrue();

    $this->roadmap->update(['unlock_policy' => UnlockPolicy::Strict]);
    $state = stateFor($this->user, $this->roadmap->refresh());

    expect($state->canProgress($this->a2))->toBeFalse()
        ->and($state->canProgress($this->a1))->toBeTrue();
});

it('runs in a fixed number of queries', function () {
    DB::enableQueryLog();
    stateFor($this->user, $this->roadmap);

    expect(DB::getQueryLog())->toHaveCount(5);
});

it('starts a lesson on open only when it is available', function () {
    $state = stateFor($this->user, $this->roadmap);

    expect($state->startsOnOpen($this->a1))->toBeTrue()
        ->and($state->startsOnOpen($this->a2))->toBeFalse();

    progressOf($this->user, $this->a1, ProgressStatus::InProgress);

    expect(stateFor($this->user, $this->roadmap)->startsOnOpen($this->a1))->toBeFalse();
});
