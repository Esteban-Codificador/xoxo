<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentStatus;
use App\Models\Pivots\SkillDependency;
use Carbon\CarbonImmutable;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $description
 * @property string|null $icon
 * @property Difficulty $difficulty
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 */
#[Fillable(['slug', 'name', 'description', 'icon', 'difficulty', 'status', 'published_at'])]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use Auditable, HasContentStatus, HasFactory;

    protected function casts(): array
    {
        return [
            'difficulty' => Difficulty::class,
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Skill, $this, SkillDependency, 'dependency'>
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'skill_dependencies', 'skill_id', 'prerequisite_skill_id')
            ->using(SkillDependency::class)
            ->as('dependency')
            ->withPivot(['kind', 'min_progress']);
    }

    /**
     * @return BelongsToMany<Skill, $this, SkillDependency, 'dependency'>
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'skill_dependencies', 'prerequisite_skill_id', 'skill_id')
            ->using(SkillDependency::class)
            ->as('dependency')
            ->withPivot(['kind', 'min_progress']);
    }

    /**
     * @return BelongsToMany<Lesson, $this>
     */
    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class)->withPivot('weight');
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
