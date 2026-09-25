<?php

namespace App\Domain\Content\Package\Importers;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportContext;
use App\Domain\Content\Package\SourceEntity;
use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\LinkStatus;
use App\Enums\ResourceType;
use App\Models\ExternalResource;
use Illuminate\Database\Eloquent\Model;

final class ResourceImporter implements EntityImporter
{
    public function type(): EntityType
    {
        return EntityType::Resource;
    }

    public function find(int $id): ?Model
    {
        return ExternalResource::query()->find($id);
    }

    public function fill(SourceEntity $entity, ?Model $model, ImportContext $context): Model
    {
        $resource = $model instanceof ExternalResource ? $model : new ExternalResource;
        $url = $entity->string('url');

        $resource->fill([
            'title' => $entity->string('title'),
            'url' => $url,
            'type' => ResourceType::from($entity->string('type')),
            'provider' => $entity->string('provider'),
            'description' => $entity->string('description'),
            'difficulty' => ($difficulty = $entity->nullableString('difficulty')) === null ? null : Difficulty::from($difficulty),
            'language' => $entity->string('language', 'en'),
            'is_official' => (bool) ($entity->data['is_official'] ?? false),
            'status' => ContentStatus::Published,
            'published_at' => $resource->published_at ?? now(),
        ]);

        // A new URL has not been verified yet.
        if ($resource->isDirty('url')) {
            $resource->fill(['link_status' => LinkStatus::Unchecked, 'last_checked_at' => null, 'last_http_status' => null]);
        }

        $resource->save();

        return $resource;
    }

    public function syncRelations(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function finalize(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function state(Model $model): array
    {
        assert($model instanceof ExternalResource);

        return [
            $model->title, $model->url, $model->type->value, $model->provider, $model->description,
            $model->difficulty?->value, $model->language, $model->is_official, $model->status->value,
        ];
    }
}
