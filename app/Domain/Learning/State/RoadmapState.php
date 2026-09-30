<?php

namespace App\Domain\Learning\State;

use App\Enums\NodeState;
use App\Enums\UnlockPolicy;
use App\Models\Lesson;
use App\Models\Track;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * One learner's position in one roadmap, computed by RoadmapStateResolver.
 * Pages read states from here; React never recalculates them.
 */
final readonly class RoadmapState
{
    /**
     * @param  array<int, TrackState>  $tracks  by track id
     * @param  array<int, LessonState>  $lessons  by lesson id
     * @param  list<int>  $studyOrder  visible lesson ids in study order
     * @param  array<int, CarbonImmutable>  $lastViewed  in-progress lesson id => last visit
     * @param  array<int, array{slug: string, title: string, track_id: int, module_slug: string, module_title: string}>  $outline  visible lesson id => where it sits
     * @param  array<int, array{slug: string, title: string}>  $trackOutline  published track id => slug and title, in study order
     * @param  int|null  $lastActivity  the lesson the learner touched last (visited, completed), whatever its state
     * @param  array<int, bool>  $quizzes  visible lesson id => whether the learner passed its published quiz
     * @param  array<int, CarbonImmutable>  $quizFailures  visible lesson id => when the learner last failed its quiz, never passed
     */
    public function __construct(
        public UnlockPolicy $policy,
        private array $tracks,
        private array $lessons,
        private array $studyOrder,
        private array $lastViewed,
        private array $outline = [],
        private array $trackOutline = [],
        private ?int $lastActivity = null,
        private array $quizzes = [],
        private array $quizFailures = [],
    ) {}

    public function track(Track|int $track): TrackState
    {
        $id = $track instanceof Track ? $track->id : $track;

        return $this->tracks[$id] ?? throw new LogicException("Track {$id} is not a published track of this roadmap.");
    }

    public function lesson(Lesson|int $lesson): LessonState
    {
        $id = $lesson instanceof Lesson ? $lesson->id : $lesson;

        return $this->lessons[$id] ?? throw new LogicException("Lesson {$id} is not visible in this roadmap.");
    }

    /** Whether the lesson has a published quiz: evidence for MASTERED (ADR-029). */
    public function hasQuiz(Lesson|int $lesson): bool
    {
        return isset($this->quizzes[$lesson instanceof Lesson ? $lesson->id : $lesson]);
    }

    /**
     * Lessons whose published quiz the learner failed and has not passed
     * yet, with the last failure, most recent first.
     *
     * @return array<int, CarbonImmutable>
     */
    public function quizFailures(): array
    {
        $failures = $this->quizFailures;
        arsort($failures);

        return $failures;
    }

    public function hasLesson(Lesson|int $lesson): bool
    {
        return isset($this->lessons[$lesson instanceof Lesson ? $lesson->id : $lesson]);
    }

    /**
     * STRICT blocks progress on LOCKED lessons; ADVISORY only warns (ADR-009).
     */
    public function canProgress(Lesson|int $lesson): bool
    {
        return $this->policy === UnlockPolicy::Advisory
            || $this->lesson($lesson)->state !== NodeState::Locked;
    }

    /**
     * The visible lessons of a track in study order, with their module and
     * state: what the roadmap panel lists without querying per track.
     *
     * @return list<array{slug: string, title: string, module_slug: string, module_title: string, state: NodeState}>
     */
    public function lessonsOf(Track|int $track): array
    {
        $trackId = $track instanceof Track ? $track->id : $track;
        $lessons = [];

        foreach ($this->studyOrder as $id) {
            $info = $this->outline[$id] ?? null;

            if ($info !== null && $info['track_id'] === $trackId) {
                $lessons[] = [
                    'slug' => $info['slug'],
                    'title' => $info['title'],
                    'module_slug' => $info['module_slug'],
                    'module_title' => $info['module_title'],
                    'state' => $this->lessons[$id]->state,
                ];
            }
        }

        return $lessons;
    }

    /**
     * Opening a lesson starts it only when it is AVAILABLE. Peeking at a
     * LOCKED lesson is reading, not starting: otherwise "continue" would
     * send the learner to a lesson whose prerequisites are missing.
     */
    public function startsOnOpen(Lesson|int $lesson): bool
    {
        return $this->lesson($lesson)->state === NodeState::Available;
    }

    /**
     * The in-progress lesson visited last, with that visit.
     *
     * @return array{0: int, 1: CarbonImmutable}|null
     */
    public function lastViewedInProgress(): ?array
    {
        if ($this->lastViewed === []) {
            return null;
        }

        $recent = $this->lastViewed;
        arsort($recent);
        $id = array_key_first($recent);

        return [$id, $recent[$id]];
    }

    /** The track of the lesson the learner touched last; null for a new learner. */
    public function lastActivityTrackId(): ?int
    {
        return $this->lastActivity === null ? null : ($this->outline[$this->lastActivity]['track_id'] ?? null);
    }

    /**
     * Published track ids in study order.
     *
     * @return list<int>
     */
    public function trackIds(): array
    {
        return array_keys($this->trackOutline);
    }

    /**
     * @return array{slug: string, title: string}
     */
    public function trackInfo(int $track): array
    {
        return $this->trackOutline[$track] ?? throw new LogicException("Track {$track} is not a published track of this roadmap.");
    }

    /**
     * Where a visible lesson sits: slug, title and track.
     *
     * @return array{slug: string, title: string, track_id: int, module_slug: string, module_title: string}
     */
    public function lessonInfo(int $lesson): array
    {
        return $this->outline[$lesson] ?? throw new LogicException("Lesson {$lesson} is not visible in this roadmap.");
    }

    /**
     * Every visible lesson id, in study order.
     *
     * @return list<int>
     */
    public function lessonIds(): array
    {
        return $this->studyOrder;
    }

    /**
     * Visible lesson ids of a track in study order.
     *
     * @return list<int>
     */
    public function lessonIdsOf(int $track): array
    {
        return array_values(array_filter($this->studyOrder, fn (int $id) => ($this->outline[$id]['track_id'] ?? null) === $track));
    }
}
