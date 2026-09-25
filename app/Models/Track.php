<?php

namespace App\Models;

use App\Casts\AsRichContent;
use App\Domain\Content\RichContent\RichContent;
use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\RecordsAuthors;
use App\Models\Pivots\TrackDependency;
use Carbon\CarbonImmutable;
use Database\Factories\TrackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property int $roadmap_id
 * @property string $slug
 * @property string $title
 * @property string $summary
 * @property RichContent|null $description
 * @property string $why_it_matters
 * @property string|null $icon
 * @property int $position
 * @property Difficulty $difficulty
 * @property int|null $estimated_hours
 * @property int|null $review_interval_months
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 */
#[Fillable([
    'roadmap_id', 'slug', 'title', 'summary', 'description', 'why_it_matters', 'icon', 'position',
    'difficulty', 'estimated_hours', 'review_interval_months', 'status', 'published_at',
])]
class Track extends Model
{
    /** @use HasFactory<TrackFactory> */
    use Auditable, HasContentStatus, HasFactory, RecordsAuthors;

    protected function casts(): array
    {
        return [
            'description' => AsRichContent::class,
            'position' => 'integer',
            'difficulty' => Difficulty::class,
            'estimated_hours' => 'integer',
            'review_interval_months' => 'integer',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Roadmap, $this>
     */
    public function roadmap(): BelongsTo
    {
        return $this->belongsTo(Roadmap::class);
    }

    /**
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('position');
    }

    /**
     * Tracks this track depends on.
     *
     * @return BelongsToMany<Track, $this, TrackDependency, 'dependency'>
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'track_dependencies', 'track_id', 'prerequisite_track_id')
            ->using(TrackDependency::class)
            ->as('dependency')
            ->withPivot(['kind', 'min_progress']);
    }

    /**
     * Tracks that depend on this track.
     *
     * @return BelongsToMany<Track, $this, TrackDependency, 'dependency'>
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'track_dependencies', 'prerequisite_track_id', 'track_id')
            ->using(TrackDependency::class)
            ->as('dependency')
            ->withPivot(['kind', 'min_progress']);
    }

    /**
     * @return MorphToMany<ExternalResource, $this>
     */
    public function resources(): MorphToMany
    {
        return $this->morphToMany(ExternalResource::class, 'linkable', 'resource_links', 'linkable_id', 'resource_id')
            ->withPivot(['position', 'note'])
            ->orderByPivot('position');
    }
}
