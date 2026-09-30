<?php

namespace App\Domain\Learning\Recommendations;

use App\Domain\Learning\State\RoadmapState;

/**
 * What a learner should study next. V1 applies rules in order
 * (RuleBasedRecommendationEngine); V2 can put an AI engine behind the same
 * interface. It receives the state already resolved: pages compute it once
 * per request and the rules only read it.
 */
interface RecommendationEngine
{
    /**
     * @return list<Recommendation> most important first
     */
    public function recommend(RoadmapState $state, int $limit = 3): array;
}
