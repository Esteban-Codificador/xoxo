<?php

namespace App\Models\Pivots;

use App\Enums\DependencyKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use LogicException;

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

    /**
     * The edge of a lesson loaded through prerequisites() or dependents(),
     * which name their pivot "dependency".
     */
    public static function of(Model $model): self
    {
        $pivot = $model->relationLoaded('dependency') ? $model->getRelation('dependency') : null;

        return $pivot instanceof self
            ? $pivot
            : throw new LogicException('The model was not loaded through a dependency relation.');
    }
}
