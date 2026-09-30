<?php

namespace App\Domain\Learning\State;

use App\Enums\DependencyKind;
use App\Enums\NodeState;
use App\Models\Pivots\SkillDependency;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Skill states from a roadmap state (architecture §6, D5, ADR-020), in
 * three queries on top of it:
 *
 *   progress(S) = Σ weight(L)·[L done] / Σ weight(L), over the visible
 *                 lessons that develop S (lesson_skill.weight, 1–5)
 *   COMPLETED   ⇔ every such lesson is done
 *   IN_PROGRESS ⇔ any of them has progress
 *   AVAILABLE   ⇔ every REQUIRED skill prerequisite P has progress(P) ≥ min
 *   LOCKED      ⇔ otherwise
 *
 *   MASTERED    ⇔ COMPLETED, at least one of its lessons has a published
 *                 quiz, and every lesson with one is MASTERED (ADR-029)
 *
 * Skills never block lessons; their blockers only say what to develop
 * first. Only published skills and visible lessons count.
 */
final class SkillProgressCalculator
{
    public function calculate(RoadmapState $roadmap): SkillsState
    {
        $studyOrder = $roadmap->lessonIds();
        $position = array_flip($studyOrder);

        $skills = Skill::query()
            ->published()
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'description', 'difficulty'])
            ->keyBy('id');

        $links = DB::table('lesson_skill')
            ->whereIn('lesson_id', $studyOrder)
            ->whereIn('skill_id', $skills->keys())
            ->get(['lesson_id', 'skill_id', 'weight'])
            ->groupBy('skill_id');

        $required = SkillDependency::query()
            ->where('kind', DependencyKind::Required->value)
            ->whereIn('skill_id', $skills->keys())
            ->whereIn('prerequisite_skill_id', $skills->keys())
            ->get()
            ->groupBy('skill_id');

        $lessons = [];
        $counts = [];
        foreach ($skills->keys() as $skillId) {
            $rows = $links->get($skillId, collect())
                ->sortBy(fn (stdClass $row) => $position[(int) $row->lesson_id])
                ->values();

            $weight = 0;
            $doneWeight = 0;
            $done = 0;
            $started = false;
            $withQuiz = 0;
            $masteredQuiz = 0;
            foreach ($rows as $row) {
                $lesson = $roadmap->lesson((int) $row->lesson_id);
                $weight += (int) $row->weight;

                if ($roadmap->hasQuiz((int) $row->lesson_id)) {
                    $withQuiz++;
                    $masteredQuiz += $lesson->state === NodeState::Mastered ? 1 : 0;
                }
                $started = $started || $lesson->state !== NodeState::Available && $lesson->state !== NodeState::Locked;

                if ($lesson->isDone()) {
                    $done++;
                    $doneWeight += (int) $row->weight;
                }
            }

            $lessons[$skillId] = array_values($rows->map(fn (stdClass $row) => (int) $row->lesson_id)->all());
            $counts[$skillId] = [
                'total' => $rows->count(),
                'completed' => $done,
                'progress' => $weight === 0 ? 0 : intdiv(100 * $doneWeight, $weight),
                'started' => $started,
                'mastered' => $withQuiz > 0 && $masteredQuiz === $withQuiz,
            ];
        }

        $states = [];
        foreach ($skills as $skillId => $skill) {
            $blockers = [];
            foreach ($required->get($skillId, collect()) as $edge) {
                $current = $counts[$edge->prerequisite_skill_id]['progress'];

                if ($current < $edge->min_progress) {
                    $prerequisite = $skills[$edge->prerequisite_skill_id];
                    $blockers[] = Blocker::skill($prerequisite->slug, $prerequisite->name, $current, $edge->min_progress);
                }
            }

            $count = $counts[$skillId];
            $complete = $count['total'] > 0 && $count['completed'] === $count['total'];
            $states[$skillId] = new SkillState(
                match (true) {
                    $complete && $count['mastered'] => NodeState::Mastered,
                    $complete => NodeState::Completed,
                    $count['started'] => NodeState::InProgress,
                    $blockers === [] => NodeState::Available,
                    default => NodeState::Locked,
                },
                $count['progress'],
                $count['completed'],
                $count['total'],
                $blockers,
            );
        }

        // In the order the roadmap develops them; skills without lessons last.
        $order = $skills->keys()->sortBy(fn (int $id) => [
            $lessons[$id] === [] ? PHP_INT_MAX : $position[$lessons[$id][0]],
            $skills[$id]->name,
        ])->values();

        return new SkillsState(
            $order->mapWithKeys(fn (int $id) => [$id => [
                'slug' => $skills[$id]->slug,
                'name' => $skills[$id]->name,
                'description' => $skills[$id]->description,
                'difficulty' => $skills[$id]->difficulty,
            ]])->all(),
            $states,
            $lessons,
        );
    }
}
