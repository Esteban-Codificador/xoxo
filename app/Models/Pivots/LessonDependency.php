<?php

namespace App\Models\Pivots;

use App\Enums\DependencyKind;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $lesson_id
 * @property int $prerequisite_lesson_id
 * @property DependencyKind $kind
 */
class LessonDependency extends Pivot
{
    protected $table = 'lesson_dependencies';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['kind' => DependencyKind::class];
    }
}
