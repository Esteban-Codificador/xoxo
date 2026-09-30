<?php

namespace App\Domain\Learning\Recommendations;

use App\Domain\Learning\State\RoadmapState;

/**
 * One deterministic recommendation rule (architecture §8): a pure function
 * of the learner's state.
 */
interface RecommendationRule
{
    /**
     * @return list<Recommendation>
     */
    public function recommend(RoadmapState $state): array;
}
