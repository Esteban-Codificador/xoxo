<?php

namespace App\Domain\Assessment;

use App\Domain\Content\Media\MediaSources;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use Illuminate\Support\Collection;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The questions of an attempt as its page shows them. While it is open:
 * prompts and choices, never the answer. Once graded: what the learner
 * answered, whether it was right and the explanation; the right answer
 * only when the attempt passed or no attempts are left (ADR-036), so a
 * failed attempt teaches without handing over the next one.
 */
final readonly class AttemptSheet
{
    public function __construct(private MediaSources $media) {}

    /**
     * @return array{questions: list<array<string, mixed>>, media: array<int, array{url: string, width: int, height: int}>}
     */
    public function for(QuizAttempt $attempt, bool $reveal): array
    {
        $order = array_flip($attempt->questions);
        /** @var Collection<int, QuizQuestion> $questions */
        $questions = QuizQuestion::query()->whereIn('id', $attempt->questions)->get()
            ->sortBy(fn (QuizQuestion $question) => $order[$question->id])
            ->values();
        $graded = ! $attempt->isOpen();
        /** @var Collection<int, QuizAttemptAnswer> $answers */
        $answers = $graded ? $attempt->answers()->get()->keyBy('quiz_question_id') : collect();

        $rows = [];
        $documents = [];
        foreach ($questions as $question) {
            $handler = $question->handler();
            $row = [
                'id' => $question->id,
                'type' => $question->type->value,
                'prompt' => $question->prompt->toArray(),
                'points' => $question->points,
                'content' => $handler->present($question->payload, self::randomizer($attempt, $question)),
            ];
            $documents[] = $question->prompt;

            if ($graded) {
                // A timed-out attempt stored no answers: nothing was answered.
                $answer = $answers->get($question->id);
                $row += [
                    'answer' => $answer?->answer,
                    'correct' => $answer->is_correct ?? false,
                    'points_awarded' => $answer->points_awarded ?? 0,
                    'explanation' => $question->explanation->toArray(),
                    'solution' => $reveal ? $handler->solution($question->payload) : null,
                ];
                $documents[] = $question->explanation;
            }

            $rows[] = $row;
        }

        return ['questions' => $rows, 'media' => $this->media->for(...$documents)];
    }

    /**
     * The same shuffle every time this attempt shows this question, and a
     * different one in the next attempt.
     */
    private static function randomizer(QuizAttempt $attempt, QuizQuestion $question): Randomizer
    {
        return new Randomizer(new Mt19937(crc32("{$attempt->id}:{$question->id}")));
    }
}
