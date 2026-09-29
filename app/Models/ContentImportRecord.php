<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $package
 * @property string $key
 * @property string $importable_type
 * @property int $importable_id
 * @property string $source_hash
 * @property string $entity_hash
 * @property CarbonImmutable $synced_at Last import or export of the entity (ADR-031).
 */
#[Fillable(['package', 'key', 'importable_type', 'importable_id', 'source_hash', 'entity_hash', 'synced_at'])]
class ContentImportRecord extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['synced_at' => 'datetime'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function importable(): MorphTo
    {
        return $this->morphTo();
    }
}
