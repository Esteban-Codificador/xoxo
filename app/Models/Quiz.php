<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\RecordsAuthors;
use Carbon\CarbonImmutable;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The quiz of a lesson (§35, ADR-036), graded on the server. Not
 * versioned (R10): edits apply right away, and each attempt keeps the
 * questions it showed. Passing it with MASTERY_THRESHOLD or more is the
 * evidence a lesson needs to be MASTERED (ADR-007).
 *
 * @property int $id
 * @property int $lesson_id
 * @property string $title
 * @property string|null $description
 * @property int $pass_threshold
 * @property int|null $time_limit_seconds
 * @property int|null $max_attempts
 * @property bool $shuffle_questions
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'lesson_id', 'title', 'description', 'pass_threshold', 'time_limit_seconds', 'max_attempts',
    'shuffle_questions', 'status', 'published_at',
])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use Auditable, HasContentStatus, HasFactory, RecordsAuthors;

    /** Score a passed attempt needs for the lesson to be MASTERED (architecture §6). */
    public const int MASTERY_THRESHOLD = 90;

    protected function casts(): array
    {
        return [
            'pass_threshold' => 'integer',
            'time_limit_seconds' => 'integer',
            'max_attempts' => 'integer',
            'shuffle_questions' => 'boolean',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<QuizAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /** Attempts a learner who used $used has left; null without a limit. */
    public function attemptsLeft(int $used): ?int
    {
        return $this->max_attempts === null ? null : max(0, $this->max_attempts - $used);
    }

    /** Whether a passed attempt with this score masters the lesson. */
    public function masters(int $score): bool
    {
        return $score >= max(self::MASTERY_THRESHOLD, $this->pass_threshold);
    }

    /**
     * Published, of a lesson learners can see.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleToLearners(Builder $query): void
    {
        $query->published()->whereHas('lesson', fn (Builder $lesson) => $lesson->visibleToLearners());
    }
}
