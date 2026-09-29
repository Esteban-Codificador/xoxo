<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Curriculum\Graph\CycleDetected;
use App\Domain\Curriculum\Graph\DependencyGraph;
use App\Enums\AuditAction;
use App\Models\Pivots\SkillDependency;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the prerequisites of a skill, keeping the skill graph acyclic
 * (§38). Same contract as SyncTrackDependencies.
 */
final readonly class SyncSkillDependencies
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<int, array{kind: string, min_progress: int}>  $dependencies  skill id => edge
     *
     * @throws CycleDetected
     */
    public function handle(Skill $skill, array $dependencies): void
    {
        $graph = DependencyGraph::fromEdges(
            SkillDependency::query()->where('skill_id', '!=', $skill->id)->get()
                ->map(fn (SkillDependency $edge) => [$edge->skill_id, $edge->prerequisite_skill_id]),
        );

        foreach (array_keys($dependencies) as $prerequisite) {
            $cycle = $graph->wouldCreateCycle($skill->id, $prerequisite);

            if ($cycle !== null) {
                throw new CycleDetected($cycle);
            }

            $graph->addEdge($skill->id, $prerequisite);
        }

        $before = $this->snapshot($skill);
        DB::transaction(fn () => $skill->prerequisites()->sync($dependencies));
        $after = $this->snapshot($skill);

        if ($before !== $after) {
            $this->audit->record(AuditAction::Updated, $skill, ['prerequisites' => $before], ['prerequisites' => $after]);
        }
    }

    /**
     * @return array<int, array{kind: string, min_progress: int}>
     */
    private function snapshot(Skill $skill): array
    {
        return SkillDependency::query()->where('skill_id', $skill->id)->orderBy('prerequisite_skill_id')->get()
            ->mapWithKeys(fn (SkillDependency $edge) => [
                $edge->prerequisite_skill_id => ['kind' => $edge->kind->value, 'min_progress' => $edge->min_progress],
            ])->all();
    }
}
