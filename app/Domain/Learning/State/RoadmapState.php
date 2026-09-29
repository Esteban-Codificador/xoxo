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
     */
    public function __construct(
        public UnlockPolicy $policy,
        private array $tracks,
        private array $lessons,
        private array $studyOrder,
        private array $lastViewed,
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
     * Where to continue: the in-progress lesson visited last, otherwise the
     * first available lesson in study order. Null when nothing is left.
     */
    public function nextLessonId(): ?int
    {
        if ($this->lastViewed !== []) {
            $recent = $this->lastViewed;
            arsort($recent);

            return array_key_first($recent);
        }

        foreach ($this->studyOrder as $id) {
            if ($this->lessons[$id]->state === NodeState::Available) {
                return $id;
            }
        }

        return null;
    }
}
