<?php

namespace App\Domain\Assessment;

use App\Models\QuizQuestion;

/**
 * Grades a set of answers (ADR-022): each question is right or wrong as a
 * whole (no partial credit) and gives its points when right. The score is
 * the percentage of points, rounded down, so 69.9 % does not pass at 70.
 */
final class QuizGrader
{
    /**
     * @param  iterable<QuizQuestion>  $questions
     * @param  array<array-key, mixed>  $answers  raw answers by question id
     */
    public function grade(iterable $questions, array $answers, int $passThreshold): GradedQuiz
    {
        $graded = [];
        $earned = 0;
        $total = 0;

        foreach ($questions as $question) {
            $handler = $question->handler();
            $answer = $handler->answer($question->payload, $answers[$question->id] ?? null);
            $correct = $answer !== null && $handler->isCorrect($question->payload, $answer);
            $points = $correct ? $question->points : 0;

            $graded[] = new GradedAnswer($question->id, $answer, $correct, $points);
            $earned += $points;
            $total += $question->points;
        }

        $score = $total === 0 ? 0 : intdiv(100 * $earned, $total);

        return new GradedQuiz($graded, $earned, $total, $score, $total > 0 && $score >= $passThreshold);
    }
}
