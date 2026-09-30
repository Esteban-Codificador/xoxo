<?php

namespace App\Domain\Learning\Recommendations\Rules;

use App\Domain\Learning\Recommendations\Recommendation;
use App\Domain\Learning\Recommendations\RecommendationRule;
use App\Domain\Learning\State\RoadmapState;
use App\Enums\RecommendationReason;
use Carbon\CarbonImmutable;

/**
 * Rule 4 (reinforcement): a quiz failed in the last WINDOW_DAYS days and
 * not passed since sends the learner back to its lesson, the most recent
 * failure first.
 */
final class ReviewLesson implements RecommendationRule
{
    public const int WINDOW_DAYS = 7;

    public function recommend(RoadmapState $state): array
    {
        $since = now()->subDays(self::WINDOW_DAYS);
        $recommendations = [];

        foreach ($state->quizFailures() as $lesson => $failedAt) {
            if ($failedAt->greaterThanOrEqualTo($since)) {
                $recommendations[] = Recommendation::lesson(RecommendationReason::Review, $state, $lesson, [
                    'failed_at' => CarbonImmutable::instance($failedAt)->toIso8601String(),
                ]);
            }
        }

        return $recommendations;
    }
}
