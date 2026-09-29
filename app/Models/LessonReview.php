<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ReviewResolution;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One submission of a lesson for review (ADR-032). Open while resolution is
 * null; the lesson is in REVIEW exactly while it has an open review.
 *
 * @property int $id
 * @property int $lesson_id
 * @property int|null $submitted_by
 * @property string|null $note
 * @property ContentStatus $previous_status
 * @property string $content_hash
 * @property CarbonImmutable $submitted_at
 * @property ReviewResolution|null $resolution
 * @property int|null $resolved_by
 * @property string|null $comment
 * @property int|null $lesson_version_id
 * @property CarbonImmutable|null $resolved_at
 */
#[Fillable([
    'lesson_id', 'submitted_by', 'note', 'previous_status', 'content_hash', 'submitted_at',
    'resolution', 'resolved_by', 'comment', 'lesson_version_id', 'resolved_at',
])]
class LessonReview extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'previous_status' => ContentStatus::class,
            'submitted_at' => 'datetime',
            'resolution' => ReviewResolution::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('resolution');
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
