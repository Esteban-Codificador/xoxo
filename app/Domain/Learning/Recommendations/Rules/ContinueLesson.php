<?php

namespace App\Domain\Learning\Recommendations\Rules;

use App\Domain\Learning\Recommendations\Recommendation;
use App\Domain\Learning\Recommendations\RecommendationRule;
use App\Domain\Learning\State\RoadmapState;
use App\Enums\RecommendationReason;

/** Rule 1: the in-progress lesson visited last. */
final class ContinueLesson implements RecommendationRule
{
    public function recommend(RoadmapState $state): array
    {
        $last = $state->lastViewedInProgress();

        if ($last === null) {
            return [];
        }

        [$lesson, $viewedAt] = $last;

        return [Recommendation::lesson(RecommendationReason::Continue, $state, $lesson, ['viewed_at' => $viewedAt->toIso8601String()])];
    }
}
