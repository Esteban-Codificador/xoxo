<?php

namespace App\Domain\Curriculum\Graph;

/**
 * Directed graph of "node depends on prerequisite" edges.
 *
 * Pure data structure used to keep the three curriculum graphs (tracks,
 * skills and lessons) acyclic and to order them for display.
 */
final class DependencyGraph
{
    /** @var array<int|string, list<int|string>> node => prerequisites */
    private array $prerequisites = [];

    /**
     * @param  iterable<array{0: int|string, 1: int|string}>  $edges  Pairs of [node, prerequisite].
     * @param  iterable<int|string>  $nodes  Nodes without edges that must still appear in orderings.
     */
    public static function fromEdges(iterable $edges, iterable $nodes = []): self
    {
        $graph = new self;

        foreach ($nodes as $node) {
            $graph->addNode($node);
        }

        foreach ($edges as [$node, $prerequisite]) {
            $graph->addEdge($node, $prerequisite);
        }

        return $graph;
    }

    public function addNode(int|string $node): void
    {
        $this->prerequisites[$node] ??= [];
    }

    public function addEdge(int|string $node, int|string $prerequisite): void
    {
        $this->addNode($node);
        $this->addNode($prerequisite);

        if (! in_array($prerequisite, $this->prerequisites[$node], true)) {
            $this->prerequisites[$node][] = $prerequisite;
        }
    }

    /**
     * @return list<int|string>
     */
    public function nodes(): array
    {
        return array_keys($this->prerequisites);
    }

    /**
     * @return list<int|string>
     */
    public function prerequisitesOf(int|string $node): array
    {
        return $this->prerequisites[$node] ?? [];
    }

    /**
     * @return list<int|string>
     */
    public function dependentsOf(int|string $node): array
    {
        $dependents = [];

        foreach ($this->prerequisites as $candidate => $prerequisites) {
            if (in_array($node, $prerequisites, true)) {
                $dependents[] = $candidate;
            }
        }

        return $dependents;
    }

    /**
     * Returns the cycle that adding "node depends on prerequisite" would
     * create, or null when the edge is safe.
     *
     * @return list<int|string>|null
     */
    public function wouldCreateCycle(int|string $node, int|string $prerequisite): ?array
    {
        if ($node === $prerequisite) {
            return [$node, $node];
        }

        $path = $this->pathBetween($prerequisite, $node);

        return $path === null ? null : [$node, ...$path];
    }

    /**
     * @return list<int|string>|null The first cycle found, with its start node repeated at the end.
     */
    public function findCycle(): ?array
    {
        $state = [];

        foreach ($this->nodes() as $start) {
            if (isset($state[$start])) {
                continue;
            }

            $cycle = $this->visit($start, $state, []);

            if ($cycle !== null) {
                return $cycle;
            }
        }

        return null;
    }

    /**
     * Nodes ordered so every prerequisite comes before its dependents.
     * Ties keep insertion order, so the result is deterministic.
     *
     * @return list<int|string>
     *
     * @throws CycleDetected
     */
    public function topologicalOrder(): array
    {
        $cycle = $this->findCycle();

        if ($cycle !== null) {
            throw new CycleDetected($cycle);
        }

        $pending = array_map(count(...), $this->prerequisites);
        $ordered = [];

        while ($pending !== []) {
            foreach ($pending as $node => $count) {
                if ($count > 0) {
                    continue;
                }

                $ordered[] = $node;
                unset($pending[$node]);

                foreach ($this->dependentsOf($node) as $dependent) {
                    if (isset($pending[$dependent])) {
                        $pending[$dependent]--;
                    }
                }

                continue 2;
            }
        }

        return $ordered;
    }

    /**
     * Rank of each node: 0 for nodes without prerequisites, otherwise one
     * more than its highest-ranked prerequisite (longest path from a root).
     *
     * @return array<int|string, int>
     *
     * @throws CycleDetected
     */
    public function ranks(): array
    {
        $ranks = [];

        foreach ($this->topologicalOrder() as $node) {
            $ranks[$node] = 0;

            foreach ($this->prerequisitesOf($node) as $prerequisite) {
                $ranks[$node] = max($ranks[$node], $ranks[$prerequisite] + 1);
            }
        }

        return $ranks;
    }

    /**
     * Depth-first search that follows prerequisite edges from $from until it
     * reaches $to.
     *
     * @return list<int|string>|null
     */
    private function pathBetween(int|string $from, int|string $to): ?array
    {
        $stack = [[$from, [$from]]];
        $seen = [$from => true];

        while ($stack !== []) {
            [$current, $path] = array_pop($stack);

            if ($current === $to) {
                return $path;
            }

            foreach ($this->prerequisitesOf($current) as $next) {
                if (! isset($seen[$next])) {
                    $seen[$next] = true;
                    $stack[] = [$next, [...$path, $next]];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int|string, int>  $state  1 = on the current path, 2 = fully explored.
     * @param  list<int|string>  $path
     * @return list<int|string>|null
     */
    private function visit(int|string $node, array &$state, array $path): ?array
    {
        $state[$node] = 1;
        $path[] = $node;

        foreach ($this->prerequisitesOf($node) as $prerequisite) {
            $prerequisiteState = $state[$prerequisite] ?? 0;

            if ($prerequisiteState === 1) {
                $start = array_search($prerequisite, $path, true);

                return [...array_slice($path, (int) $start), $prerequisite];
            }

            if ($prerequisiteState === 0) {
                $cycle = $this->visit($prerequisite, $state, $path);

                if ($cycle !== null) {
                    return $cycle;
                }
            }
        }

        $state[$node] = 2;

        return null;
    }
}
