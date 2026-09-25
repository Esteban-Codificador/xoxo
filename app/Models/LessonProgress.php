<?php

namespace App\Models;

use App\Enums\ProgressStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $lesson_id
 * @property ProgressStatus $status
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $mastered_at
 * @property CarbonImmutable|null $last_viewed_at
 * @property int|null $completed_version_id
 */
#[Fillable(['user_id', 'lesson_id', 'status', 'started_at', 'completed_at', 'mastered_at', 'last_viewed_at', 'completed_version_id'])]
class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected function casts(): array
    {
        return [
            'status' => ProgressStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'mastered_at' => 'datetime',
            'last_viewed_at' => 'datetime',
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
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
