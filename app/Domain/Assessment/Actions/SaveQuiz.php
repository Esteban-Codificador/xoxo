<?php

namespace App\Domain\Assessment\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Content\RichContent\RichContent;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

/**
 * Creates the quiz of a lesson on the first save (as a draft) and replaces
 * its questions in order: a question keeps its id while it stays, so its
 * answer history stays with it; a removed one takes its answers along.
 * Attempts keep their score (ADR-036). Question rows have no audit of
 * their own: the change is logged on the quiz.
 */
final readonly class SaveQuiz
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{title: string, description: string|null, pass_threshold: int, time_limit_seconds: int|null, max_attempts: int|null, shuffle_questions: bool}  $settings
     * @param  list<array{id: int|null, type: QuestionType, prompt: RichContent, payload: array<string, mixed>, explanation: RichContent, difficulty: Difficulty|null, points: int}>  $questions
     */
    public function handle(Lesson $lesson, array $settings, array $questions): Quiz
    {
        return DB::transaction(function () use ($lesson, $settings, $questions) {
            $quiz = Quiz::query()->whereBelongsTo($lesson)->lockForUpdate()->first()
                ?? new Quiz(['lesson_id' => $lesson->id, 'status' => ContentStatus::Draft]);
            $before = $quiz->exists ? self::snapshot($quiz) : [];

            $quiz->fill($settings)->save();

            $existing = $quiz->questions()->get()->keyBy('id');
            $kept = [];
            foreach ($questions as $index => $data) {
                $question = ($data['id'] === null ? null : $existing->get($data['id'])) ?? new QuizQuestion(['quiz_id' => $quiz->id]);
                $question->fill([
                    'type' => $data['type'],
                    'prompt' => $data['prompt'],
                    'payload' => $data['payload'],
                    'explanation' => $data['explanation'],
                    'difficulty' => $data['difficulty'],
                    'points' => $data['points'],
                    'position' => $index + 1,
                ])->save();
                $kept[] = $question->id;
            }

            $quiz->questions()->whereNotIn('id', $kept)->delete();

            $after = self::snapshot($quiz);

            if ($before !== $after) {
                $this->audit->record(AuditAction::Updated, $quiz, $before === [] ? [] : ['questions' => $before], ['questions' => $after]);
            }

            return $quiz;
        });
    }

    /**
     * How many questions and a hash of all of them, in order.
     *
     * @return array{count: int, sha256: string}
     */
    private static function snapshot(Quiz $quiz): array
    {
        $questions = $quiz->questions()->get()->map(fn (QuizQuestion $question) => [
            $question->type->value,
            $question->prompt->toArray(),
            $question->payload,
            $question->explanation->toArray(),
            $question->difficulty?->value,
            $question->points,
        ])->all();

        return [
            'count' => count($questions),
            'sha256' => substr(hash('sha256', (string) json_encode($questions)), 0, 16),
        ];
    }
}
