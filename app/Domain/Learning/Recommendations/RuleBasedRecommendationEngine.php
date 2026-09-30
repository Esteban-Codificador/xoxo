<?php

namespace App\Domain\Learning\Recommendations;

use App\Domain\Learning\Recommendations\Rules\ContinueLesson;
use App\Domain\Learning\Recommendations\Rules\NextLesson;
use App\Domain\Learning\Recommendations\Rules\UnlockTrack;
use App\Domain\Learning\State\RoadmapState;

/**
 * The V1 rules in order (architecture §8): continue, next lesson, unlock.
 * Earlier rules win: a subject is recommended once, with the first reason.
 */
final readonly class RuleBasedRecommendationEngine implements RecommendationEngine
{
    /** @var list<RecommendationRule> */
    private array $rules;

    /**
     * @param  list<RecommendationRule>|null  $rules
     */
    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? [new ContinueLesson, new NextLesson, new UnlockTrack];
    }

    public function recommend(RoadmapState $state, int $limit = 3): array
    {
        $recommendations = [];

        foreach ($this->rules as $rule) {
            foreach ($rule->recommend($state) as $recommendation) {
                $recommendations[$recommendation->key()] ??= $recommendation;
            }
        }

        $ordered = array_slice(array_values($recommendations), 0, max(0, $limit));

        return array_map(
            fn (Recommendation $recommendation, int $index) => $recommendation->withPriority($index + 1),
            $ordered,
            array_keys($ordered),
        );
    }
}
