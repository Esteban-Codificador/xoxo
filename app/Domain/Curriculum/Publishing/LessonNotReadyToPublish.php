<?php

namespace App\Domain\Curriculum\Publishing;

use RuntimeException;

final class LessonNotReadyToPublish extends RuntimeException
{
    /**
     * @param  list<ReadinessIssue>  $issues
     */
    public function __construct(public readonly array $issues)
    {
        parent::__construct(implode(' ', array_map(fn (ReadinessIssue $issue) => $issue->message(), $issues)));
    }
}
