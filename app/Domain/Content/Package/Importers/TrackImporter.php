<?php

namespace App\Domain\Content\Package\Importers;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportContext;
use App\Domain\Content\Package\SourceEntity;
use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;
use App\Enums\Difficulty;
use App\Models\Pivots\TrackDependency;
use App\Models\Track;
use Illuminate\Database\Eloquent\Model;

final class TrackImporter implements EntityImporter
{
    use PublishesByStatus;

    public function __construct(private readonly MarkdownToRichContent $markdown) {}

    public function type(): EntityType
    {
        return EntityType::Track;
    }

    public function find(int $id): ?Model
    {
        return Track::query()->find($id);
    }

    public function fill(SourceEntity $entity, ?Model $model, ImportContext $context): Model
    {
        $track = $model instanceof Track ? $model : new Track;

        $track->fill([
            'roadmap_id' => $context->id(EntityType::Roadmap, $entity->parentKey),
            'slug' => $entity->string('slug'),
            'title' => $entity->string('title'),
            'summary' => $entity->string('summary'),
            'why_it_matters' => $entity->string('why_it_matters'),
            'description' => ($entity->body ?? '') === '' ? null : $this->markdown->convert((string) $entity->body),
            'icon' => $entity->nullableString('icon'),
            'position' => $entity->position,
            'difficulty' => Difficulty::from($entity->string('difficulty')),
            'estimated_hours' => $entity->int('estimated_hours'),
            'review_interval_months' => $entity->int('review_interval_months'),
            ...$this->statusAttributes($entity, $model),
        ])->save();

        return $track;
    }

    public function syncRelations(SourceEntity $entity, Model $model, ImportContext $context): void
    {
        assert($model instanceof Track);

        $prerequisites = [];

        foreach ($entity->list('depends_on') as $dependency) {
            $prerequisites[(string) $dependency['track']] = ['kind' => $dependency['kind'], 'min_progress' => $dependency['min_progress']];
        }

        $model->prerequisites()->sync($context->resolve($entity, EntityType::Track, $prerequisites));
    }

    public function finalize(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function state(Model $model): array
    {
        assert($model instanceof Track);

        return [
            $model->roadmap_id, $model->slug, $model->title, $model->summary, $model->why_it_matters, $model->description?->hash(),
            $model->icon, $model->position, $model->difficulty->value, $model->estimated_hours, $model->review_interval_months, $model->status->value,
            TrackDependency::query()->where('track_id', $model->id)->orderBy('prerequisite_track_id')->get()
                ->map(fn (TrackDependency $d) => [$d->prerequisite_track_id, $d->kind->value, $d->min_progress])->all(),
        ];
    }
}
