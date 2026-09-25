<?php

namespace App\Domain\Content\Package\Importers;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportContext;
use App\Domain\Content\Package\SourceEntity;
use App\Models\Module;
use Illuminate\Database\Eloquent\Model;

final class ModuleImporter implements EntityImporter
{
    use PublishesByStatus;

    public function type(): EntityType
    {
        return EntityType::Module;
    }

    public function find(int $id): ?Model
    {
        return Module::query()->find($id);
    }

    public function fill(SourceEntity $entity, ?Model $model, ImportContext $context): Model
    {
        $module = $model instanceof Module ? $model : new Module;

        $module->fill([
            'track_id' => $context->id(EntityType::Track, $entity->parentKey),
            'slug' => $entity->string('slug'),
            'title' => $entity->string('title'),
            'summary' => $entity->string('summary'),
            'position' => $entity->position,
            ...$this->statusAttributes($entity, $model),
        ])->save();

        return $module;
    }

    public function syncRelations(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function finalize(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function state(Model $model): array
    {
        assert($model instanceof Module);

        return [$model->track_id, $model->slug, $model->title, $model->summary, $model->position, $model->status->value];
    }
}
