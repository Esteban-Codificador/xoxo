<?php

namespace App\Domain\Content\Package\Importers;

use App\Domain\Content\Package\SourceEntity;
use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;

trait PublishesByStatus
{
    /**
     * @return array{status: ContentStatus, published_at: mixed}
     */
    private function statusAttributes(SourceEntity $entity, ?Model $model): array
    {
        $status = ContentStatus::from($entity->string('status', ContentStatus::Published->value));

        return [
            'status' => $status,
            'published_at' => $status === ContentStatus::Published ? ($model?->getAttribute('published_at') ?? now()) : $model?->getAttribute('published_at'),
        ];
    }
}
