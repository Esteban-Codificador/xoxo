<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One try at a quiz. The clock is the server's: expires_at is set when it
 * starts, and an answer sent after it (plus GRACE_SECONDS for the network)
 * is not graded.
 *
 * @property int $id
 * @property int $user_id
 * @property int $quiz_id
 * @property int $attempt_number
 * @property list<int> $questions question ids in the order shown
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $submitted_at
 * @property bool $timed_out
 * @property int|null $score
 * @property int|null $points_earned
 * @property int|null $points_total
 * @property bool|null $passed
 */
#[Fillable([
    'user_id', 'quiz_id', 'attempt_number', 'questions', 'started_at', 'expires_at', 'submitted_at',
    'timed_out', 'score', 'points_earned', 'points_total', 'passed',
])]
class QuizAttempt extends Model
{
    /** Time an answer sent at the last second has to arrive. */
    public const int GRACE_SECONDS = 30;

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'questions' => 'array',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'timed_out' => 'boolean',
            'score' => 'integer',
            'points_earned' => 'integer',
            'points_total' => 'integer',
            'passed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return HasMany<QuizAttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    public function isOpen(): bool
    {
        return $this->submitted_at === null;
    }

    /** Open, and too late for its answers to count. */
    public function isOverdue(): bool
    {
        return $this->isOpen()
            && $this->expires_at !== null
            && now()->greaterThan($this->expires_at->addSeconds(self::GRACE_SECONDS));
    }

    /** Seconds left on the clock; null when the quiz has no time limit. */
    public function secondsLeft(): ?int
    {
        // Rounded up: the clock stored whole seconds, and the grace period covers the rest.
        return $this->expires_at === null ? null : max(0, (int) ceil(now()->diffInSeconds($this->expires_at, false)));
    }
}
