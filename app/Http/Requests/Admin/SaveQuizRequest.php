<?php

namespace App\Http\Requests\Admin;

use App\Domain\Assessment\QuestionTypes\QuestionTypes;
use App\Domain\Content\RichContent\RichContent;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Lesson;
use App\Rules\RichContentDocument;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The quiz of a lesson, settings and questions together, created on the
 * first save. Each question's payload is checked by its type
 * (QuestionTypeHandler::problems), with errors on the field that has them.
 */
class SaveQuizRequest extends FormRequest
{
    public const int MAX_QUESTIONS = 50;

    public function authorize(): Response
    {
        return Gate::inspect('update', $this->lesson());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'pass_threshold' => ['required', 'integer', 'between:1,100'],
            'time_limit_minutes' => ['nullable', 'integer', 'between:1,180'],
            'max_attempts' => ['nullable', 'integer', 'between:1,100'],
            'shuffle_questions' => ['required', 'boolean'],
            'questions' => ['present', 'list', 'max:'.self::MAX_QUESTIONS],
            'questions.*' => ['array'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.type' => ['required', Rule::enum(QuestionType::class)],
            'questions.*.prompt' => ['required', new RichContentDocument],
            'questions.*.payload' => ['required', 'array'],
            'questions.*.explanation' => ['required', new RichContentDocument],
            'questions.*.difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'questions.*.points' => ['required', 'integer', 'between:1,10'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $questions = $this->input('questions');

            if (! is_array($questions)) {
                return;
            }

            foreach ($questions as $index => $question) {
                if (! is_array($question)) {
                    continue;
                }

                $type = QuestionType::tryFrom((string) ($question['type'] ?? ''));

                if ($type !== null) {
                    foreach (QuestionTypes::for($type)->problems($question['payload'] ?? null) as $path => $message) {
                        $validator->errors()->add("questions.{$index}.payload.{$path}", $message);
                    }
                }

                foreach (['prompt', 'explanation'] as $field) {
                    $key = "questions.{$index}.{$field}";

                    if (! $validator->errors()->has($key) && self::isEmptyDocument($question[$field] ?? null)) {
                        $validator->errors()->add($key, __("quizzes.{$field}_required"));
                    }
                }
            }

            // Learners are taking it: a published quiz keeps at least one question.
            if ($questions === [] && $this->lesson()->quiz?->isPublished()) {
                $validator->errors()->add('questions', __('quizzes.published_needs_questions'));
            }
        }];
    }

    /**
     * @return array{title: string, description: string|null, pass_threshold: int, time_limit_seconds: int|null, max_attempts: int|null, shuffle_questions: bool}
     */
    public function settings(): array
    {
        $minutes = $this->validated('time_limit_minutes');
        $attempts = $this->validated('max_attempts');

        return [
            'title' => (string) $this->validated('title'),
            'description' => $this->validated('description') === null ? null : (string) $this->validated('description'),
            'pass_threshold' => (int) $this->validated('pass_threshold'),
            'time_limit_seconds' => $minutes === null ? null : 60 * (int) $minutes,
            'max_attempts' => $attempts === null ? null : (int) $attempts,
            'shuffle_questions' => (bool) $this->validated('shuffle_questions'),
        ];
    }

    /**
     * The questions in order, payloads normalized by their type.
     *
     * @return list<array{id: int|null, type: QuestionType, prompt: RichContent, payload: array<string, mixed>, explanation: RichContent, difficulty: Difficulty|null, points: int}>
     */
    public function questions(): array
    {
        /** @var list<array{id?: int|string|null, type: string, prompt: array<string, mixed>, payload: array<string, mixed>, explanation: array<string, mixed>, difficulty?: string|null, points: int|string}> $rows */
        $rows = $this->validated('questions');

        return array_map(function (array $row): array {
            $type = QuestionType::from($row['type']);

            return [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'type' => $type,
                'prompt' => RichContent::fromArray($row['prompt']),
                'payload' => QuestionTypes::for($type)->normalize($row['payload']),
                'explanation' => RichContent::fromArray($row['explanation']),
                'difficulty' => isset($row['difficulty']) ? Difficulty::from($row['difficulty']) : null,
                'points' => (int) $row['points'],
            ];
        }, $rows);
    }

    public function lesson(): Lesson
    {
        $lesson = $this->route('lesson');

        return $lesson instanceof Lesson ? $lesson : abort(404);
    }

    private static function isEmptyDocument(mixed $value): bool
    {
        try {
            return RichContent::fromArray($value)->isEmpty();
        } catch (\Throwable) {
            return false;
        }
    }
}
