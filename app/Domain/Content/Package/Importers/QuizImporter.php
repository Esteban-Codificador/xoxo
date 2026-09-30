<?php

namespace App\Domain\Content\Package\Importers;

use App\Domain\Assessment\QuestionTypes\QuestionTypes;
use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportContext;
use App\Domain\Content\Package\QuizQuestions;
use App\Domain\Content\Package\SourceEntity;
use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Quizzes of the package (quizzes/*.yaml), one per lesson. Questions are
 * matched by position: one that keeps its place keeps its row and its
 * answer history; one beyond the new count is removed with its answers.
 */
final class QuizImporter implements EntityImporter
{
    use PublishesByStatus;

    public function __construct(private readonly MarkdownToRichContent $markdown) {}

    public function type(): EntityType
    {
        return EntityType::Quiz;
    }

    public function find(int $id): ?Model
    {
        return Quiz::query()->find($id);
    }

    public function fill(SourceEntity $entity, ?Model $model, ImportContext $context): Model
    {
        $lessonId = $context->id(EntityType::Lesson, $entity->parentKey)
            ?? throw new LogicException("{$entity->file}: la lección \"{$entity->parentKey}\" no se importó.");
        // The lesson's quiz made in the CMS before this package: adopted, not duplicated.
        $quiz = $model instanceof Quiz ? $model : (Quiz::query()->where('lesson_id', $lessonId)->first() ?? new Quiz);
        $minutes = $entity->int('time_limit_minutes');

        $quiz->fill([
            'lesson_id' => $lessonId,
            'title' => $entity->string('title'),
            'description' => $entity->nullableString('description'),
            'pass_threshold' => $entity->int('pass_threshold', 70),
            'time_limit_seconds' => $minutes === null ? null : 60 * $minutes,
            'max_attempts' => $entity->int('max_attempts'),
            'shuffle_questions' => ($entity->data['shuffle_questions'] ?? true) !== false,
            ...$this->statusAttributes($entity, $model),
        ])->save();

        $existing = $quiz->questions()->get()->values();
        $kept = [];

        foreach ($entity->list('questions') as $index => $data) {
            $type = QuestionType::from((string) $data['type']);
            $question = $existing->get($index) ?? new QuizQuestion(['quiz_id' => $quiz->id]);
            $question->fill([
                'type' => $type,
                'prompt' => $this->markdown->convert((string) $data['prompt'], $context->images),
                'payload' => QuestionTypes::for($type)->normalize(QuizQuestions::payload($type, $data)),
                'explanation' => $this->markdown->convert((string) $data['explanation'], $context->images),
                'difficulty' => isset($data['difficulty']) ? Difficulty::from((string) $data['difficulty']) : null,
                'points' => is_int($data['points'] ?? null) ? $data['points'] : 1,
                'position' => $index + 1,
            ])->save();
            $kept[] = $question->id;
        }

        $quiz->questions()->whereNotIn('id', $kept)->delete();

        return $quiz;
    }

    public function syncRelations(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function finalize(SourceEntity $entity, Model $model, ImportContext $context): void {}

    public function state(Model $model): array
    {
        assert($model instanceof Quiz);

        return [
            $model->lesson_id, $model->title, $model->description, $model->pass_threshold, $model->time_limit_seconds,
            $model->max_attempts, $model->shuffle_questions, $model->status->value,
            $model->questions()->get()->map(fn (QuizQuestion $question) => [
                $question->type->value, $question->prompt->hash(), self::canonical($question->payload),
                $question->explanation->hash(), $question->difficulty?->value, $question->points,
            ])->all(),
        ];
    }

    /**
     * jsonb does not keep key order: sorted keys hash the same every time.
     */
    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::canonical(...), $value);
    }
}
