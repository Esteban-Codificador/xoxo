<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Curriculum\Graph\CycleDetected;
use App\Domain\Curriculum\Graph\DependencyGraph;
use App\Enums\AuditAction;
use App\Models\Lesson;
use App\Models\Pivots\LessonDependency;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the skills, prerequisites and resources of a lesson. Relations
 * are not versioned (TD-6): learners see them right away, filtered to what
 * is published. The lesson graph must stay acyclic (§25).
 */
final readonly class SyncLessonRelations
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<int, int>  $skills  skill id => weight (1–5)
     * @param  array<int, string>  $prerequisites  lesson id => kind
     * @param  list<int>  $resources  resource ids in display order
     *
     * @throws CycleDetected
     */
    public function handle(Lesson $lesson, array $skills, array $prerequisites, array $resources): void
    {
        $graph = DependencyGraph::fromEdges(
            LessonDependency::query()->where('lesson_id', '!=', $lesson->id)->get()
                ->map(fn (LessonDependency $edge) => [$edge->lesson_id, $edge->prerequisite_lesson_id]),
        );

        foreach (array_keys($prerequisites) as $prerequisite) {
            $cycle = $graph->wouldCreateCycle($lesson->id, $prerequisite);

            if ($cycle !== null) {
                throw new CycleDetected($cycle);
            }

            $graph->addEdge($lesson->id, $prerequisite);
        }

        $before = $this->snapshot($lesson);

        DB::transaction(function () use ($lesson, $skills, $prerequisites, $resources): void {
            $lesson->skills()->sync(array_map(fn (int $weight) => ['weight' => $weight], $skills));
            $lesson->prerequisites()->sync(array_map(fn (string $kind) => ['kind' => $kind], $prerequisites));
            // Keeps the note of resources that stay; only the order changes.
            $lesson->resources()->sync(collect($resources)->mapWithKeys(fn (int $id, int $index) => [$id => ['position' => $index + 1]])->all());
        });

        $after = $this->snapshot($lesson);
        $changed = array_keys(array_filter($after, fn (array $value, string $key) => $before[$key] !== $value, ARRAY_FILTER_USE_BOTH));

        // Pivot rows have no model events: the change is logged on the lesson.
        if ($changed !== []) {
            $this->audit->record(
                AuditAction::Updated,
                $lesson,
                array_intersect_key($before, array_flip($changed)),
                array_intersect_key($after, array_flip($changed)),
            );
        }
    }

    /**
     * @return array{skills: array<int, int>, prerequisites: array<int, string>, resources: list<int>}
     */
    private function snapshot(Lesson $lesson): array
    {
        $skills = DB::table('lesson_skill')->where('lesson_id', $lesson->id)->orderBy('skill_id')->get(['skill_id', 'weight'])
            ->mapWithKeys(fn (object $row) => [(int) $row->skill_id => (int) $row->weight])->all();
        $prerequisites = LessonDependency::query()->where('lesson_id', $lesson->id)->orderBy('prerequisite_lesson_id')->get()
            ->mapWithKeys(fn (LessonDependency $edge) => [$edge->prerequisite_lesson_id => $edge->kind->value])->all();
        $resources = array_values(DB::table('resource_links')
            ->where('linkable_type', $lesson->getMorphClass())->where('linkable_id', $lesson->id)
            ->orderBy('position')->pluck('resource_id')->map(fn (mixed $id) => (int) $id)->all());

        return ['skills' => $skills, 'prerequisites' => $prerequisites, 'resources' => $resources];
    }
}
