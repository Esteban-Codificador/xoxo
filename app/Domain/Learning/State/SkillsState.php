<?php

namespace App\Domain\Learning\State;

use App\Enums\Difficulty;
use App\Enums\NodeState;
use LogicException;

/**
 * Every published skill for one learner, in the order the roadmap develops
 * them, computed by SkillProgressCalculator.
 */
final readonly class SkillsState
{
    /**
     * @param  array<int, array{slug: string, name: string, description: string, difficulty: Difficulty}>  $skills  by id, in order
     * @param  array<int, SkillState>  $states  by skill id
     * @param  array<int, list<int>>  $lessons  skill id => visible lesson ids developing it, in study order
     */
    public function __construct(
        private array $skills,
        private array $states,
        private array $lessons,
    ) {}

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_keys($this->skills);
    }

    public function idOf(string $slug): ?int
    {
        foreach ($this->skills as $id => $skill) {
            if ($skill['slug'] === $slug) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @return array{slug: string, name: string, description: string, difficulty: Difficulty}
     */
    public function info(int $skill): array
    {
        return $this->skills[$skill] ?? throw new LogicException("Skill {$skill} is not published.");
    }

    public function state(int $skill): SkillState
    {
        return $this->states[$skill] ?? throw new LogicException("Skill {$skill} is not published.");
    }

    /**
     * @return list<int>
     */
    public function lessonIdsOf(int $skill): array
    {
        return $this->lessons[$skill] ?? [];
    }

    /** Skills with every lesson completed. */
    public function completedCount(): int
    {
        return count(array_filter(
            $this->states,
            fn (SkillState $state) => in_array($state->state, [NodeState::Completed, NodeState::Mastered], true),
        ));
    }

    public function count(): int
    {
        return count($this->skills);
    }
}
