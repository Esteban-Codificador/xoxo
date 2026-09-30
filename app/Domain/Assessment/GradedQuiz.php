<?php

namespace App\Domain\Assessment;

final readonly class GradedQuiz
{
    /**
     * @param  list<GradedAnswer>  $answers
     */
    public function __construct(
        public array $answers,
        public int $pointsEarned,
        public int $pointsTotal,
        public int $score,
        public bool $passed,
    ) {}
}
