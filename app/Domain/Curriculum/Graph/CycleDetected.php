<?php

namespace App\Domain\Curriculum\Graph;

use RuntimeException;

final class CycleDetected extends RuntimeException
{
    /**
     * @param  list<int|string>  $path  Nodes of the cycle; the first node is repeated at the end.
     */
    public function __construct(public readonly array $path)
    {
        parent::__construct('Dependency cycle detected: '.implode(' → ', $path));
    }
}
