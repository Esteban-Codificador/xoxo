<?php

use App\Domain\Learning\Recommendations\Recommendation;
use App\Domain\Learning\Recommendations\RecommendationEngine;
use App\Domain\Learning\State\RoadmapStateResolver;
use App\Enums\DependencyKind;
use App\Enums\ProgressStatus;
use App\Enums\UnlockPolicy;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Roadmap;
use App\Models\Track;
use App\Models\User;

/*
| The V1 recommendation rules (architecture §8) on a two-track roadmap:
|   A (position 0): a1 → a2 (a2 REQUIRES a1)
|   B (position 1): b1; B REQUIRES A at 100 %
*/

beforeEach(function () {
    $this->roadmap = Roadmap::factory()->published()->create(['unlock_policy' => UnlockPolicy::Advisory]);
    $this->trackA = Track::factory()->published()->for($this->roadmap)->create(['slug' => 'a', 'title' => 'Track A', 'position' => 0]);
    $this->trackB = Track::factory()->published()->for($this->roadmap)->create(['slug' => 'b', 'title' => 'Track B', 'position' => 1]);
    $moduleA = Module::factory()->published()->for($this->trackA)->create();
    $moduleB = Module::factory()->published()->for($this->trackB)->create();

    $this->a1 = publishedLesson(['slug' => 'a1', 'title' => 'Lección A1', 'position' => 1], $moduleA);
    $this->a2 = publishedLesson(['slug' => 'a2', 'title' => 'Lección A2', 'position' => 2], $moduleA);
    $this->b1 = publishedLesson(['slug' => 'b1', 'title' => 'Lección B1', 'position' => 1], $moduleB);
    $this->a2->prerequisites()->attach($this->a1, ['kind' => DependencyKind::Required->value]);
    $this->trackB->prerequisites()->attach($this->trackA, ['kind' => DependencyKind::Required->value, 'min_progress' => 100]);

    $this->user = User::factory()->create();
});

function studied(User $user, Lesson $lesson, ProgressStatus $status, string $at): void
{
    LessonProgress::create([
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'status' => $status,
        'started_at' => $at,
        'completed_at' => $status === ProgressStatus::InProgress ? null : $at,
        'last_viewed_at' => $at,
    ]);
}

/**
 * @return list<array<string, mixed>>
 */
function recommendationsFor(User $user, Roadmap $roadmap, int $limit = 3): array
{
    $state = app(RoadmapStateResolver::class)->resolve($user, $roadmap);

    return array_map(fn (Recommendation $recommendation) => $recommendation->toArray(), app(RecommendationEngine::class)->recommend($state, $limit));
}

it('sends a new learner to the first available lesson and nothing else', function () {
    expect(recommendationsFor($this->user, $this->roadmap))->toBe([[
        'reason' => 'START',
        'subject' => ['type' => 'lesson', 'slug' => 'a1', 'title' => 'Lección A1', 'track' => 'Track A'],
        'params' => [],
        'priority' => 1,
    ]]);
});

it('resumes the lesson in progress and explains what the next track needs', function () {
    studied($this->user, $this->a1, ProgressStatus::InProgress, '2026-09-28 10:00:00');

    $recommendations = recommendationsFor($this->user, $this->roadmap);

    // a2 and b1 are locked: nothing new is available, so rule 2 stays silent.
    expect(array_column($recommendations, 'reason'))->toBe(['CONTINUE', 'UNLOCK'])
        ->and($recommendations[0]['subject']['slug'])->toBe('a1')
        ->and($recommendations[0]['params']['viewed_at'])->toStartWith('2026-09-28T10:00:00')
        ->and($recommendations[1])->toMatchArray([
            'subject' => ['type' => 'track', 'slug' => 'a', 'title' => 'Track A', 'track' => null],
            'params' => ['next' => 'Track B', 'progress' => 0, 'required' => 100],
            'priority' => 2,
        ]);
});

it('recommends the next lesson in the track of the last activity', function () {
    studied($this->user, $this->a1, ProgressStatus::Completed, '2026-09-28 10:00:00');

    $recommendations = recommendationsFor($this->user, $this->roadmap);

    expect(array_column($recommendations, 'reason'))->toBe(['NEXT_IN_TRACK', 'UNLOCK'])
        ->and($recommendations[0]['subject']['slug'])->toBe('a2')
        ->and($recommendations[1]['params'])->toBe(['next' => 'Track B', 'progress' => 50, 'required' => 100]);
});

it('moves on to the next track once the current one has nothing left', function () {
    studied($this->user, $this->a1, ProgressStatus::Completed, '2026-09-28 10:00:00');
    studied($this->user, $this->a2, ProgressStatus::Completed, '2026-09-29 10:00:00');

    expect(recommendationsFor($this->user, $this->roadmap))->toBe([[
        'reason' => 'NEXT_TRACK',
        'subject' => ['type' => 'lesson', 'slug' => 'b1', 'title' => 'Lección B1', 'track' => 'Track B'],
        'params' => [],
        'priority' => 1,
    ]]);
});

it('follows the most recent activity and resumes the last lesson visited', function () {
    studied($this->user, $this->a1, ProgressStatus::Completed, '2026-09-20 10:00:00');
    studied($this->user, $this->a2, ProgressStatus::Completed, '2026-09-21 10:00:00');
    // Under ADVISORY a learner may open a later lesson; the older visit is not resumed.
    $b2 = publishedLesson(['slug' => 'b2', 'title' => 'Lección B2', 'position' => 2], $this->b1->module);
    studied($this->user, $b2, ProgressStatus::InProgress, '2026-09-25 10:00:00');

    $recommendations = recommendationsFor($this->user, $this->roadmap);

    expect(array_column($recommendations, 'reason'))->toBe(['CONTINUE', 'NEXT_IN_TRACK'])
        ->and(array_column(array_column($recommendations, 'subject'), 'slug'))->toBe(['b2', 'b1']);
});

it('recommends nothing when everything is done, and honours the limit', function () {
    studied($this->user, $this->a1, ProgressStatus::InProgress, '2026-09-28 10:00:00');
    expect(recommendationsFor($this->user, $this->roadmap, limit: 1))->toHaveCount(1);

    LessonProgress::query()->delete();
    foreach ([$this->a1, $this->a2, $this->b1] as $lesson) {
        studied($this->user, $lesson, ProgressStatus::Completed, '2026-09-28 10:00:00');
    }

    expect(recommendationsFor($this->user, $this->roadmap))->toBe([]);
});

it('does not ask to unlock what a recommended prerequisite never locks', function () {
    $this->trackB->prerequisites()->updateExistingPivot($this->trackA->id, ['kind' => DependencyKind::Recommended->value]);
    studied($this->user, $this->a1, ProgressStatus::Completed, '2026-09-28 10:00:00');

    expect(array_column(recommendationsFor($this->user, $this->roadmap), 'reason'))->toBe(['NEXT_IN_TRACK']);
});

it('sends the learner back to a lesson whose quiz they failed this week', function () {
    studied($this->user, $this->a1, ProgressStatus::Completed, '2026-09-20 10:00:00');
    studied($this->user, $this->a2, ProgressStatus::Completed, '2026-09-20 11:00:00');
    $this->travelTo('2026-09-30 12:00:00');
    $failed = function (Lesson $lesson, string $at, bool $passed = false): void {
        QuizAttempt::query()->create([
            'user_id' => $this->user->id, 'quiz_id' => Quiz::factory()->for($lesson)->create()->id, 'attempt_number' => 1, 'questions' => [],
            'started_at' => $at, 'submitted_at' => $at, 'score' => $passed ? 100 : 40, 'points_earned' => 0, 'points_total' => 5, 'passed' => $passed,
        ]);
    };
    $failed($this->a1, '2026-09-29 09:00:00');
    // Failed too long ago: no longer a nudge.
    $failed($this->a2, '2026-09-20 09:00:00');

    $recommendations = recommendationsFor($this->user, $this->roadmap, limit: 5);
    $review = collect($recommendations)->firstWhere('reason', 'REVIEW');

    expect(array_column($recommendations, 'reason'))->toContain('REVIEW')
        ->and($review['subject']['slug'])->toBe('a1')
        ->and($review['params']['failed_at'])->toStartWith('2026-09-29T09:00:00')
        ->and(collect($recommendations)->where('reason', 'REVIEW'))->toHaveCount(1);

    // Passing it later ends the nudge.
    QuizAttempt::query()->whereBelongsTo($this->user)->where('passed', false)->first()->quiz->attempts()->create([
        'user_id' => $this->user->id, 'attempt_number' => 2, 'questions' => [], 'started_at' => now(), 'submitted_at' => now(),
        'score' => 90, 'points_earned' => 0, 'points_total' => 5, 'passed' => true,
    ]);

    expect(array_column(recommendationsFor($this->user, $this->roadmap, limit: 5), 'reason'))->not->toContain('REVIEW');
});
