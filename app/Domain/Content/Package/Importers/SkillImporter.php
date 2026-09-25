<?php

namespace App\Domain\Content\Package\Importers;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportContext;
use App\Domain\Content\Package\SourceEntity;
use App\Enums\Difficulty;
use App\Models\Pivots\SkillDependency;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Model;

final class SkillImporter implements EntityImporter
{
    use PublishesByStatus;

    public function type(): EntityType
    {
        return EntityType::Skill;
    }

    public function find(int $id): ?Model
    {
        return Skill::query()->find($id);
    }

    public function fill(SourceEntity $entity, ?Model $model, ImportContext $context): Model
    {
        $skill = $model instanceof Skill ? $model : new Skill;

        $skill->fill([
            'slug' => $entity->key,
            'name' => $entity->string('name'),
            'description' => trim((string) preg_replace('/\s+/', ' ', (string) $entity->body)),
            'icon' => $entity->nullableString('icon'),
            'difficulty' => Difficulty::from($entity->string('difficulty')),
            ...$this->statusAttributes($entity, $model),
        ])->save();

        return $skill;
    }

    public function syncRelations(SourceEntity $entity, Model $model, ImportContext $context): void
    {
        assert($model instanceof Skill);

        $prerequisites = [];

        foreach ($entity->list('depends_on') as $dependency) {
            $prerequisites[(string) $dependency['skill']] = ['kind' => $dependency['kind'], 'min_progress' => $dependency['min_progress']];
        }

        $model->prerequisites()->sync($context->resolve($entity, EntityType::Skill, $prerequisites));
    }

    public function finalize(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function state(Model $model): array
    {
        assert($model instanceof Skill);

        return [
            $model->slug, $model->name, $model->description, $model->icon, $model->difficulty->value, $model->status->value,
            SkillDependency::query()->where('skill_id', $model->id)->orderBy('prerequisite_skill_id')->get()
                ->map(fn (SkillDependency $d) => [$d->prerequisite_skill_id, $d->kind->value, $d->min_progress])->all(),
        ];
    }
}
