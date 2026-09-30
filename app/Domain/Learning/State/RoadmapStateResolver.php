<?php

namespace App\Domain\Learning\State;

use App\Enums\ContentStatus;
use App\Enums\DependencyKind;
use App\Enums\NodeState;
use App\Enums\ProgressStatus;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Pivots\LessonDependency;
use App\Models\Pivots\TrackDependency;
use App\Models\Roadmap;
use App\Models\Track;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Computes every lesson and track state of a roadmap for one learner in a
 * single pass over six queries (architecture §6, ADR-007):
 *
 *   Lesson: its lesson_progress status if any; otherwise AVAILABLE when the
 *           track is unlocked and every REQUIRED prerequisite lesson is
 *           COMPLETED or MASTERED; otherwise LOCKED.
 *   Track unlocked ⇔ every REQUIRED prerequisite track P has progress(P) ≥ min_progress.
 *   Track: MASTERED at 100 % when it has at least one published quiz and
 *          the learner passed every one (ADR-029; projects join in phase
 *          6); COMPLETED at 100 %; IN_PROGRESS when any lesson has
 *          progress; AVAILABLE when unlocked; LOCKED otherwise.
 *   A lesson is MASTERED when lesson_progress says so (LessonMastery).
 *
 * Only published, visible content counts: an unpublished prerequisite can
 * never be met by a learner, so it is ignored rather than blocking forever.
 */
final class RoadmapStateResolver
{
    public function resolve(User $user, Roadmap $roadmap): RoadmapState
    {
        /** @var Collection<int, Track> $tracks */
        $tracks = Track::query()
            ->published()
            ->whereBelongsTo($roadmap)
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'slug', 'title'])
            ->keyBy('id');

        $lessons = Lesson::query()
            ->visibleToLearners()
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->join('tracks', 'tracks.id', '=', 'modules.track_id')
            ->join('lesson_versions', 'lesson_versions.id', '=', 'lessons.published_version_id')
            ->whereIn('modules.track_id', $tracks->keys())
            ->orderBy('tracks.position')->orderBy('tracks.id')
            ->orderBy('modules.position')->orderBy('modules.id')
            ->orderBy('lessons.position')->orderBy('lessons.id')
            ->toBase()
            ->get(['lessons.id', 'lessons.slug', 'lesson_versions.title', 'modules.track_id', 'modules.slug as module_slug', 'modules.title as module_title'])
            ->keyBy('id');

        $progress = LessonProgress::query()
            ->whereBelongsTo($user)
            ->whereIn('lesson_id', $lessons->keys())
            ->get(['lesson_id', 'status', 'started_at', 'completed_at', 'last_viewed_at'])
            ->keyBy('lesson_id');

        $requiredLessons = LessonDependency::query()
            ->whereIn('lesson_id', $lessons->keys())
            ->where('kind', DependencyKind::Required->value)
            ->get()
            ->filter(fn (LessonDependency $edge) => $lessons->has($edge->prerequisite_lesson_id))
            ->groupBy('lesson_id');

        $requiredTracks = TrackDependency::query()
            ->whereIn('track_id', $tracks->keys())
            ->where('kind', DependencyKind::Required->value)
            ->get()
            ->filter(fn (TrackDependency $edge) => $tracks->has($edge->prerequisite_track_id))
            ->groupBy('track_id');

        // Evidence (ADR-029): the published quizzes of these lessons, whether
        // this learner passed each one and, if not, when they last failed it.
        $quizRows = DB::table('quizzes')
            ->leftJoin('quiz_attempts', fn (JoinClause $join) => $join
                ->on('quiz_attempts.quiz_id', '=', 'quizzes.id')
                ->where('quiz_attempts.user_id', $user->id)
                ->whereNotNull('quiz_attempts.submitted_at'))
            ->where('quizzes.status', ContentStatus::Published->value)
            ->whereIn('quizzes.lesson_id', $lessons->keys())
            ->groupBy('quizzes.lesson_id')
            ->selectRaw('quizzes.lesson_id, coalesce(bool_or(quiz_attempts.passed), false) as passed, max(quiz_attempts.submitted_at) filter (where not quiz_attempts.passed) as failed_at')
            ->get();
        $quizzes = $quizRows->mapWithKeys(fn (object $row) => [(int) $row->lesson_id => (bool) $row->passed]);
        $quizFailures = $quizRows
            ->filter(fn (object $row) => ! $row->passed && $row->failed_at !== null)
            ->mapWithKeys(fn (object $row) => [(int) $row->lesson_id => CarbonImmutable::parse((string) $row->failed_at)]);

        $isDone = fn (int $lessonId): bool => in_array(
            $progress->get($lessonId)?->status,
            [ProgressStatus::Completed, ProgressStatus::Mastered],
            true,
        );

        // Track progress first: unlocking depends on it, not on other unlocks,
        // so no recursion is needed.
        $counts = [];
        foreach ($tracks->keys() as $trackId) {
            $ids = $lessons->where('track_id', $trackId)->keys();
            $done = $ids->filter(fn (int $id) => $isDone($id))->count();
            $evidence = $quizzes->only($ids->all());
            $counts[$trackId] = [
                'total' => $ids->count(),
                'completed' => $done,
                'progress' => $ids->isEmpty() ? 0 : intdiv(100 * $done, $ids->count()),
                'started' => $ids->contains(fn (int $id) => $progress->has($id)),
                'mastered' => $evidence->isNotEmpty() && $evidence->every(fn (bool $passed) => $passed),
            ];
        }

        $trackStates = [];
        foreach ($tracks as $trackId => $track) {
            $blockers = [];
            foreach ($requiredTracks->get($trackId, collect()) as $edge) {
                $current = $counts[$edge->prerequisite_track_id]['progress'];

                if ($current < $edge->min_progress) {
                    $prerequisite = $tracks[$edge->prerequisite_track_id];
                    $blockers[] = Blocker::track($prerequisite->slug, $prerequisite->title, $current, $edge->min_progress);
                }
            }

            $count = $counts[$trackId];
            $complete = $count['total'] > 0 && $count['completed'] === $count['total'];
            $state = match (true) {
                $complete && $count['mastered'] => NodeState::Mastered,
                $complete => NodeState::Completed,
                $count['started'] => NodeState::InProgress,
                $blockers === [] => NodeState::Available,
                default => NodeState::Locked,
            };

            $trackStates[$trackId] = new TrackState($state, $count['progress'], $count['completed'], $count['total'], $blockers);
        }

        $lessonStates = [];
        foreach ($lessons as $lessonId => $lesson) {
            $blockers = $trackStates[$lesson->track_id]->blockers;

            foreach ($requiredLessons->get($lessonId, collect()) as $edge) {
                if (! $isDone($edge->prerequisite_lesson_id)) {
                    $prerequisite = $lessons[$edge->prerequisite_lesson_id];
                    $blockers[] = Blocker::lesson($prerequisite->slug, $prerequisite->title);
                }
            }

            $status = $progress->get($lessonId)?->status;
            $state = match (true) {
                $status === ProgressStatus::Mastered => NodeState::Mastered,
                $status === ProgressStatus::Completed => NodeState::Completed,
                $status === ProgressStatus::InProgress => NodeState::InProgress,
                $blockers === [] => NodeState::Available,
                default => NodeState::Locked,
            };

            $lessonStates[$lessonId] = new LessonState($state, $blockers);
        }

        $lastViewed = $progress
            ->filter(fn (LessonProgress $row) => $row->status === ProgressStatus::InProgress)
            ->map(fn (LessonProgress $row) => $row->last_viewed_at ?? $row->getAttribute('created_at'))
            ->filter()
            ->all();

        // Visiting or completing a lesson is activity; the latest one wins.
        // Starting happens on the first visit, so it only counts without one.
        $lastActivity = $progress
            ->sortByDesc(fn (LessonProgress $row) => max(
                ($row->last_viewed_at ?? $row->started_at)->getTimestamp(),
                $row->completed_at?->getTimestamp() ?? 0,
            ))
            ->keys()
            ->first();

        return new RoadmapState(
            $roadmap->unlock_policy,
            $trackStates,
            $lessonStates,
            array_values($lessons->keys()->map(fn (int|string $id) => (int) $id)->all()),
            $lastViewed,
            $lessons->map(fn (object $lesson) => [
                'slug' => (string) $lesson->slug,
                'title' => (string) $lesson->title,
                'track_id' => (int) $lesson->track_id,
                'module_slug' => (string) $lesson->module_slug,
                'module_title' => (string) $lesson->module_title,
            ])->all(),
            $tracks->map(fn (Track $track) => ['slug' => $track->slug, 'title' => $track->title])->all(),
            $lastActivity === null ? null : (int) $lastActivity,
            $quizzes->all(),
            $quizFailures->all(),
        );
    }
}
