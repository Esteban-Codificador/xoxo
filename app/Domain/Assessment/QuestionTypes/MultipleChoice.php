<?php

namespace App\Domain\Assessment\QuestionTypes;

/**
 * One or more right options; right only with exactly those marked (no
 * partial credit). Answer: {choices: [id]} in the author's order.
 */
final class MultipleChoice extends ChoiceQuestion
{
    protected function correctCountProblem(int $correct): ?string
    {
        return $correct >= 1 ? null : Texts::message('multiple_correct');
    }

    public function answer(array $payload, mixed $raw): ?array
    {
        $choices = is_array($raw) ? ($raw['choices'] ?? null) : null;

        if (! is_array($choices)) {
            return null;
        }

        $chosen = array_values(array_filter($this->ids($payload), fn (string $id) => in_array($id, $choices, true)));

        return $chosen === [] ? null : ['choices' => $chosen];
    }

    public function isCorrect(array $payload, array $answer): bool
    {
        return ($answer['choices'] ?? null) === $this->correctIds($payload);
    }

    public function solution(array $payload): array
    {
        return ['choices' => $this->correctIds($payload)];
    }
}
