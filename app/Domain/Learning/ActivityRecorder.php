<?php

namespace App\Domain\Learning;

use App\Enums\ActivityType;
use App\Models\LearningActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Appends to the learning feed (learning_activities). XP is always 0 until
 * gamification arrives (phase 7); the table already makes XP idempotent.
 */
final class ActivityRecorder
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(User $user, ActivityType $type, Model $subject, array $metadata = []): LearningActivity
    {
        $now = now();

        return LearningActivity::create([
            'user_id' => $user->id,
            'type' => $type,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'xp' => 0,
            'metadata' => $metadata,
            'occurred_at' => $now,
            'occurred_on' => $now->toDateString(),
        ]);
    }
}
