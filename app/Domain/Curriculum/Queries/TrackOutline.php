<?php

namespace App\Domain\Curriculum\Queries;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Track;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * The study order of a track as a learner sees it: published modules by
 * position, each with its visible lessons by position. Modules without a
 * visible lesson are left out. The track page lists it and the lesson page
 * takes its previous and next lesson from it, so both always agree.
 */
final class TrackOutline
{
    /** @var Collection<int, Module> */
    public readonly Collection $modules;

    private function __construct(public readonly Track $track)
    {
        $this->modules = $track->modules()
            ->published()
            ->with('visibleLessons.publishedVersion')
            ->reorder()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->filter(fn (Module $module) => $module->visibleLessons->isNotEmpty())
            ->values();
    }

    public static function for(Track $track): self
    {
        return new self($track);
    }

    /**
     * @return SupportCollection<int, Lesson>
     */
    public function lessons(): SupportCollection
    {
        return $this->modules->flatMap(fn (Module $module) => $module->visibleLessons)->values();
    }

    /**
     * @return array{previous: Lesson|null, next: Lesson|null}
     */
    public function neighboursOf(Lesson $lesson): array
    {
        $lessons = $this->lessons();
        $index = $lessons->search(fn (Lesson $candidate) => $candidate->is($lesson));

        if ($index === false) {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $lessons->get($index - 1),
            'next' => $lessons->get($index + 1),
        ];
    }

    public function totalMinutes(): int
    {
        return (int) $this->lessons()->sum(fn (Lesson $lesson) => $lesson->publishedVersion->estimated_minutes);
    }
}
