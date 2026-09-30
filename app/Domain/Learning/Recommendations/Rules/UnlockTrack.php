<?php

namespace App\Domain\Learning\Recommendations\Rules;

use App\Domain\Learning\Recommendations\Recommendation;
use App\Domain\Learning\Recommendations\RecommendationRule;
use App\Domain\Learning\State\Blocker;
use App\Domain\Learning\State\RoadmapState;
use App\Enums\NodeState;
use App\Enums\RecommendationReason;

/**
 * Rule 3: when the next track after the one of the last activity is
 * LOCKED, its unmet prerequisite, with how far the learner is and how far
 * it must go ("llevas 50 %, se requiere 100 %"). Only once the learner has
 * started: before that there is nothing to continue.
 */
final class UnlockTrack implements RecommendationRule
{
    public function recommend(RoadmapState $state): array
    {
        $anchor = $state->lastActivityTrackId();

        if ($anchor === null) {
            return [];
        }

        $tracks = $state->trackIds();
        $after = array_slice($tracks, (int) array_search($anchor, $tracks, true) + 1);

        foreach ($after as $track) {
            $next = $state->track($track);

            if ($next->state === NodeState::Completed) {
                continue;
            }

            if ($next->state !== NodeState::Locked) {
                return [];
            }

            $blocker = array_values(array_filter($next->blockers, fn (Blocker $blocker) => $blocker->type === 'track'))[0] ?? null;

            return $blocker === null ? [] : [Recommendation::track(RecommendationReason::Unlock, $blocker->slug, $blocker->title, [
                'next' => $state->trackInfo($track)['title'],
                'progress' => (int) $blocker->progress,
                'required' => (int) $blocker->required,
            ])];
        }

        return [];
    }
}
