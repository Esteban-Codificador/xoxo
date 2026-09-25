<?php

namespace App\Models\Pivots;

use App\Enums\DependencyKind;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $skill_id
 * @property int $prerequisite_skill_id
 * @property DependencyKind $kind
 * @property int $min_progress
 */
class SkillDependency extends Pivot
{
    protected $table = 'skill_dependencies';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['kind' => DependencyKind::class, 'min_progress' => 'integer'];
    }
}
