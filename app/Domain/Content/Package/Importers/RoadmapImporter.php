<?php

namespace App\Domain\Content\Package\Importers;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportContext;
use App\Domain\Content\Package\SourceEntity;
use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;
use App\Enums\UnlockPolicy;
use App\Models\Roadmap;
use Illuminate\Database\Eloquent\Model;

final class RoadmapImporter implements EntityImporter
{
    use PublishesByStatus;

    public function __construct(private readonly MarkdownToRichContent $markdown) {}

    public function type(): EntityType
    {
        return EntityType::Roadmap;
    }

    public function find(int $id): ?Model
    {
        return Roadmap::query()->find($id);
    }

    public function fill(SourceEntity $entity, ?Model $model, ImportContext $context): Model
    {
        $roadmap = $model instanceof Roadmap ? $model : new Roadmap;

        $roadmap->fill([
            'slug' => $entity->string('slug'),
            'title' => $entity->string('title'),
            'summary' => $entity->string('summary'),
            'description' => ($entity->body ?? '') === '' ? null : $this->markdown->convert((string) $entity->body),
            'locale' => $entity->string('locale', 'es'),
            'unlock_policy' => UnlockPolicy::from($entity->string('unlock_policy')),
            'mastery_threshold' => $entity->int('mastery_threshold', 90),
            ...$this->statusAttributes($entity, $model),
        ])->save();

        return $roadmap;
    }

    public function syncRelations(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function finalize(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function state(Model $model): array
    {
        assert($model instanceof Roadmap);

        return [
            $model->slug, $model->title, $model->summary, $model->description?->hash(), $model->locale,
            $model->unlock_policy->value, $model->mastery_threshold, $model->status->value,
        ];
    }
}
