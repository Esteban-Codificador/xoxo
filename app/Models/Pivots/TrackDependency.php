<?php

namespace App\Models\Pivots;

use App\Enums\DependencyKind;
use Illuminate\Database\Eloquent\Relations\Pivot;

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
}
