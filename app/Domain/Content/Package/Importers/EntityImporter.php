<?php

namespace App\Domain\Content\Package\Importers;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportContext;
use App\Domain\Content\Package\SourceEntity;
use Illuminate\Database\Eloquent\Model;

interface EntityImporter
{
    public function type(): EntityType;

    public function find(int $id): ?Model;

    /**
     * Creates or updates the model from the source entity and saves it.
     */
    public function fill(SourceEntity $entity, ?Model $model, ImportContext $context): Model;

    /**
     * Syncs relations once every entity of this type exists.
     */
    public function syncRelations(SourceEntity $entity, Model $model, ImportContext $context): void;

    /**
     * Last step (publishing, for lessons).
     */
    public function finalize(SourceEntity $entity, Model $model, ImportContext $context): void;

    /**
     * Everything the importer controls. Its hash detects edits made in the CMS.
     *
     * @return list<mixed>
     */
    public function state(Model $model): array;
}
