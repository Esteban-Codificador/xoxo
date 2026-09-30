<?php

namespace App\Domain\Assessment\Actions;

use App\Domain\Learning\ActivityRecorder;
use App\Enums\ActivityType;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

/**
 * Closes an attempt whose time ran out without its answers arriving (the
 * page auto-submits at zero, so this is a closed tab or a lost connection):
 * it fails with 0 and no answer rows, so it does not count against any
 * question. The caller holds the lock on the attempt.
 */
final readonly class ExpireQuizAttempt
{
    public function __construct(private ActivityRecorder $activities) {}

    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->loadMissing(['user', 'quiz']);

        $attempt->update([
            'submitted_at' => $attempt->expires_at ?? now(),
            'timed_out' => true,
            'score' => 0,
            'points_earned' => 0,
            'points_total' => (int) QuizQuestion::query()->whereIn('id', $attempt->questions)->sum('points'),
            'passed' => false,
        ]);

        $this->activities->record($attempt->user, ActivityType::QuizFailed, $attempt->quiz, [
            'attempt' => $attempt->attempt_number,
            'score' => 0,
            'timed_out' => true,
        ]);

        return $attempt;
    }
}
