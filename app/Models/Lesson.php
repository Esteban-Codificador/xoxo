<?php

namespace App\Models;

use App\Casts\AsRichContent;
use App\Domain\Content\RichContent\RichContent;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\Difficulty;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\RecordsAuthors;
use App\Models\Pivots\LessonDependency;
use Carbon\CarbonImmutable;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Working copy of a lesson. Learners read published_version, never these
 * columns directly (ADR-006).
 *
 * @property int $id
 * @property int $module_id
 * @property string $slug
 * @property string $title
 * @property string $summary
 * @property string $why_it_matters
 * @property list<string> $learning_objectives
 * @property RichContent $body
 * @property ContentType $content_type
 * @property Difficulty $difficulty
 * @property int $estimated_minutes
 * @property int $position
 * @property ContentStatus $status
 * @property int|null $published_version_id
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $last_reviewed_at
 */
#[Fillable([
    'module_id', 'slug', 'title', 'summary', 'why_it_matters', 'learning_objectives', 'body', 'content_type',
    'difficulty', 'estimated_minutes', 'position', 'status', 'last_reviewed_at',
])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use Auditable, HasContentStatus, HasFactory, RecordsAuthors;

    /** Fields copied into every published version, in snapshot order. */
    public const array VERSIONED_FIELDS = [
        'title', 'summary', 'why_it_matters', 'learning_objectives', 'body', 'content_type', 'difficulty', 'estimated_minutes',
    ];

    protected function casts(): array
    {
        return [
            'learning_objectives' => 'array',
            'body' => AsRichContent::class,
            'content_type' => ContentType::class,
            'difficulty' => Difficulty::class,
            'estimated_minutes' => 'integer',
            'position' => 'integer',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'last_reviewed_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * @return HasMany<LessonVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(LessonVersion::class)->orderByDesc('version');
    }

    /**
     * @return BelongsTo<LessonVersion, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(LessonVersion::class, 'published_version_id');
    }

    /**
     * @return BelongsToMany<Lesson, $this, LessonDependency, 'dependency'>
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'lesson_dependencies', 'lesson_id', 'prerequisite_lesson_id')
            ->using(LessonDependency::class)
            ->as('dependency')
            ->withPivot('kind');
    }

    /**
     * @return BelongsToMany<Lesson, $this, LessonDependency, 'dependency'>
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'lesson_dependencies', 'prerequisite_lesson_id', 'lesson_id')
            ->using(LessonDependency::class)
            ->as('dependency')
            ->withPivot('kind');
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('weight');
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

    /**
     * @return HasMany<LessonProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Hash of the versioned fields of the working copy. Equal to the
     * content_hash of the published version when there are no unpublished changes.
     */
    public function workingCopyHash(): string
    {
        return LessonVersion::hashFields($this->versionedFields());
    }

    public function hasUnpublishedChanges(): bool
    {
        $this->loadMissing('publishedVersion');

        return $this->publishedVersion?->content_hash !== $this->workingCopyHash();
    }

    /**
     * @return array<string, mixed>
     */
    public function versionedFields(): array
    {
        return [
            'title' => $this->title,
            'summary' => $this->summary,
            'why_it_matters' => $this->why_it_matters,
            'learning_objectives' => $this->learning_objectives,
            'body' => $this->body->toArray(),
            'content_type' => $this->content_type->value,
            'difficulty' => $this->difficulty->value,
            'estimated_minutes' => $this->estimated_minutes,
        ];
    }

    /**
     * Lessons a learner can see: they have a published version, are not
     * archived and belong to a published module, track and roadmap.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visibleToLearners(Builder $query): void
    {
        // Qualified: callers join modules (Track::lessons() is has-many-through).
        $query->whereNotNull($this->qualifyColumn('published_version_id'))
            ->where($this->qualifyColumn('status'), '!=', ContentStatus::Archived->value)
            ->whereHas('module', fn (Builder $module) => $module
                ->published()
                ->whereHas('track', fn (Builder $track) => $track
                    ->published()
                    ->whereHas('roadmap', fn (Builder $roadmap) => $roadmap->published())));
    }
}
