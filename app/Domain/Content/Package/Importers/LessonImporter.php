<?php

namespace App\Domain\Content\Package\Importers;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportContext;
use App\Domain\Content\Package\SourceEntity;
use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;
use App\Domain\Curriculum\Actions\PublishLesson;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\Difficulty;
use App\Models\Lesson;
use App\Models\Pivots\LessonDependency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class LessonImporter implements EntityImporter
{
    public const string CHANGE_NOTE = 'Importada desde el paquete de contenido.';

    public function __construct(
        private readonly MarkdownToRichContent $markdown,
        private readonly PublishLesson $publish,
    ) {}

    public function type(): EntityType
    {
        return EntityType::Lesson;
    }

    public function find(int $id): ?Model
    {
        return Lesson::query()->find($id);
    }

    public function fill(SourceEntity $entity, ?Model $model, ImportContext $context): Model
    {
        $lesson = $model instanceof Lesson ? $model : new Lesson;
        $status = ContentStatus::from($entity->string('status', ContentStatus::Published->value));

        $lesson->fill([
            'module_id' => $context->id(EntityType::Module, $entity->parentKey),
            'slug' => $entity->string('slug'),
            'title' => $entity->string('title'),
            'summary' => $entity->string('summary'),
            'why_it_matters' => $entity->string('why_it_matters'),
            'learning_objectives' => array_map(strval(...), $entity->list('objectives')),
            'body' => $this->markdown->convert((string) $entity->body),
            'content_type' => ContentType::from($entity->string('type')),
            'difficulty' => Difficulty::from($entity->string('difficulty')),
            'estimated_minutes' => $entity->int('estimated_minutes', 30),
            'position' => $entity->position,
            'last_reviewed_at' => $entity->nullableString('last_reviewed'),
            // PUBLISHED is reached through PublishLesson in finalize(), never by writing the column.
            'status' => $status === ContentStatus::Published ? ($lesson->status ?? ContentStatus::Draft) : $status,
        ])->save();

        return $lesson;
    }

    public function syncRelations(SourceEntity $entity, Model $model, ImportContext $context): void
    {
        assert($model instanceof Lesson);

        $skills = [];
        foreach ($entity->list('skills') as $skill) {
            $skills[(string) $skill['key']] = ['weight' => $skill['weight']];
        }

        $prerequisites = [];
        foreach ($entity->list('depends_on') as $dependency) {
            $prerequisites[(string) $dependency['lesson']] = ['kind' => $dependency['kind']];
        }

        $resources = [];
        foreach ($entity->list('resources') as $position => $key) {
            $resources[(string) $key] = ['position' => $position];
        }

        $model->skills()->sync($context->resolve($entity, EntityType::Skill, $skills));
        $model->prerequisites()->sync($context->resolve($entity, EntityType::Lesson, $prerequisites));
        $model->resources()->sync($context->resolve($entity, EntityType::Resource, $resources));
    }

    public function finalize(SourceEntity $entity, Model $model, ImportContext $context): void
    {
        assert($model instanceof Lesson);

        if ($entity->isPublished()) {
            $this->publish->handle($model->refresh(), self::CHANGE_NOTE);
        }
    }

    public function state(Model $model): array
    {
        assert($model instanceof Lesson);

        return [
            $model->module_id, $model->slug, $model->position, $model->status->value, $model->last_reviewed_at?->format('Y-m-d'),
            $model->workingCopyHash(),
            DB::table('lesson_skill')->where('lesson_id', $model->id)->orderBy('skill_id')->get(['skill_id', 'weight'])
                ->map(fn (object $row) => [(int) $row->skill_id, (int) $row->weight])->all(),
            LessonDependency::query()->where('lesson_id', $model->id)->orderBy('prerequisite_lesson_id')->get()
                ->map(fn (LessonDependency $d) => [$d->prerequisite_lesson_id, $d->kind->value])->all(),
            DB::table('resource_links')->where('linkable_type', $model->getMorphClass())->where('linkable_id', $model->id)
                ->orderBy('position')->pluck('resource_id')->map(fn (mixed $id) => (int) $id)->all(),
        ];
    }
}
