<?php

namespace App\Domain\Assessment;

final readonly class GradedAnswer
{
    /**
     * @param  array<string, mixed>|null  $answer  canonical; null when not answered
     */
    public function __construct(
        public int $questionId,
        public ?array $answer,
        public bool $correct,
        public int $points,
    ) {}
}
