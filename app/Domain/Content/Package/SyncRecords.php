<?php

namespace App\Domain\Content\Package;

use App\Domain\Content\Package\Importers\EntityImporter;
use App\Models\ContentImportRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * The last sync point between a package entity and its database row
 * (content_import_records): the hash of the file and the hash of the row at
 * that moment. content:import writes it after importing, content:export
 * after exporting (ADR-031). Comparing both hashes with the current ones
 * tells what changed since: the file, the row (edited in the CMS) or both.
 */
final class SyncRecords
{
    public function find(string $package, SourceEntity $entity): ?ContentImportRecord
    {
        return ContentImportRecord::query()->where('package', $package)->where('key', $entity->recordKey())->first();
    }

    public function save(string $package, SourceEntity $entity, Model $model, EntityImporter $importer): void
    {
        ContentImportRecord::query()->updateOrCreate(
            ['package' => $package, 'key' => $entity->recordKey()],
            [
                'importable_type' => $model->getMorphClass(),
                'importable_id' => $model->getKey(),
                'source_hash' => $entity->hash(),
                'entity_hash' => $this->stateHash($importer, $model),
                'synced_at' => now(),
            ],
        );
    }

    /**
     * Hash of everything the importer controls on the row.
     */
    public function stateHash(EntityImporter $importer, Model $model): string
    {
        return hash('sha256', (string) json_encode($importer->state($model), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
