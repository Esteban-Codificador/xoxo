<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $track_id
 * @property string $slug
 * @property string $title
 * @property string $summary
 * @property int $position
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 */
#[Fillable(['track_id', 'slug', 'title', 'summary', 'position', 'status', 'published_at'])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use Auditable, HasContentStatus, HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Track, $this>
     */
    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    /**
     * Lessons a learner can open, in study order (see Lesson::visibleToLearners()).
     *
     * @return HasMany<Lesson, $this>
     */
    public function visibleLessons(): HasMany
    {
        return $this->lessons()->visibleToLearners()->orderBy('id');
    }
}
