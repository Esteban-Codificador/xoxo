<?php

namespace App\Domain\Learning\Recommendations\Rules;

use App\Domain\Learning\Recommendations\Recommendation;
use App\Domain\Learning\Recommendations\RecommendationRule;
use App\Domain\Learning\State\RoadmapState;
use App\Enums\NodeState;
use App\Enums\RecommendationReason;

/**
 * Rule 2: the first AVAILABLE lesson, in study order, of the track of the
 * last activity. When that track has none left, the search goes on through
 * the next tracks (and then the earlier ones), so finishing a track does
 * not leave the learner without a next step. A new learner gets the first
 * available lesson of the roadmap.
 */
final class NextLesson implements RecommendationRule
{
    public function recommend(RoadmapState $state): array
    {
        $anchor = $state->lastActivityTrackId();
        $tracks = $state->trackIds();

        if ($anchor !== null && ($at = array_search($anchor, $tracks, true)) !== false) {
            $tracks = [...array_slice($tracks, $at), ...array_slice($tracks, 0, $at)];
        }

        foreach ($tracks as $track) {
            foreach ($state->lessonIdsOf($track) as $lesson) {
                if ($state->lesson($lesson)->state !== NodeState::Available) {
                    continue;
                }

                $reason = match (true) {
                    $anchor === null => RecommendationReason::Start,
                    $track === $anchor => RecommendationReason::NextInTrack,
                    default => RecommendationReason::NextTrack,
                };

                return [Recommendation::lesson($reason, $state, $lesson)];
            }
        }

        return [];
    }
}
