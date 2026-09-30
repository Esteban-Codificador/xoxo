<?php

namespace App\Enums;

/**
 * Why a lesson or track is recommended (architecture §8). The dashboard
 * turns it into a sentence with i18n; it is never stored as text.
 */
enum RecommendationReason: string
{
    use HasValues;

    /** Rule 1: the in-progress lesson visited last. */
    case Continue = 'CONTINUE';

    /** Rule 2, new learner: the first available lesson of the roadmap. */
    case Start = 'START';

    /** Rule 2: the next available lesson in the track of the last activity. */
    case NextInTrack = 'NEXT_IN_TRACK';

    /** Rule 2, that track has none left: the first available lesson further on. */
    case NextTrack = 'NEXT_TRACK';

    /** Rule 3: the unmet prerequisite of the next track, which is LOCKED. */
    case Unlock = 'UNLOCK';
}
