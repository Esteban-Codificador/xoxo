<?php

namespace App\Models\Pivots;

use App\Enums\DependencyKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use LogicException;

/**
 * @property int $track_id
 * @property int $prerequisite_track_id
 * @property DependencyKind $kind
 * @property int $min_progress
 */
class TrackDependency extends Pivot
{
    protected $table = 'track_dependencies';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['kind' => DependencyKind::class, 'min_progress' => 'integer'];
    }

    /**
     * The edge of a track loaded through prerequisites() or dependents(),
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
