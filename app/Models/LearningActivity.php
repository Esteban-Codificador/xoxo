<?php

namespace App\Models;

use App\Enums\ActivityType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Append-only learning event: activity feed, streak source and XP ledger.
 *
 * @property int $id
 * @property int $user_id
 * @property ActivityType $type
 * @property string $subject_type
 * @property int $subject_id
 * @property int $xp
 * @property array<string, mixed> $metadata
 * @property CarbonImmutable $occurred_at
 * @property CarbonImmutable $occurred_on
 */
#[Fillable(['user_id', 'type', 'subject_type', 'subject_id', 'xp', 'metadata', 'occurred_at', 'occurred_on'])]
class LearningActivity extends Model
{
    public const null UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Learning activities are append-only.'));
    }

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'xp' => 'integer',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'occurred_on' => 'date',
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
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
