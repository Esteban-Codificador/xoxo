<?php

namespace App\Domain\Content\Package;

use App\Enums\QuestionType;

/**
 * Questions of quizzes/*.yaml. Each one has its type, points (1 when
 * absent), difficulty, statement and explanation (Markdown), and its
 * answer data under the same names the stored payload uses:
 *
 *   options: [{text, correct}]   SINGLE_CHOICE, MULTIPLE_CHOICE
 *   answer: true|false            TRUE_FALSE
 *   items: [text, …]              ORDERING (in the right order)
 *   pairs: [{left, right}]        MATCHING
 */
final class QuizQuestions
{
    /**
     * The stored payload of a question as the package writes it.
     *
     * @param  array<mixed>  $question
     * @return array<string, mixed>
     */
    public static function payload(QuestionType $type, array $question): array
    {
        $field = self::field($type);

        return [$field => $question[$field] ?? null];
    }

    public static function field(QuestionType $type): string
    {
        return match ($type) {
            QuestionType::SingleChoice, QuestionType::MultipleChoice => 'options',
            QuestionType::TrueFalse => 'answer',
            QuestionType::Ordering => 'items',
            QuestionType::Matching => 'pairs',
        };
    }
}
