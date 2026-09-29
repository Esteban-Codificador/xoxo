<?php

namespace App\Domain\Learning\State;

use App\Enums\NodeState;

final readonly class LessonState
{
    /**
     * @param  list<Blocker>  $blockers
     */
    public function __construct(
        public NodeState $state,
        public array $blockers,
    ) {}

    public function isDone(): bool
    {
        return $this->state === NodeState::Completed || $this->state === NodeState::Mastered;
    }
}
