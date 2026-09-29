<?php

namespace App\Domain\Learning\State;

use App\Enums\NodeState;

final readonly class TrackState
{
    /**
     * @param  list<Blocker>  $blockers
     */
    public function __construct(
        public NodeState $state,
        public int $progress,
        public int $completed,
        public int $total,
        public array $blockers,
    ) {}

    /**
     * @return array{state: string, progress: int, completed: int, total: int, blockers: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,
            'progress' => $this->progress,
            'completed' => $this->completed,
            'total' => $this->total,
            'blockers' => array_map(fn (Blocker $blocker) => $blocker->toArray(), $this->blockers),
        ];
    }
}
