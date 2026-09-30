<?php

namespace App\Domain\Assessment\QuestionTypes;

/** One right option. Answer: {choice: id}. */
final class SingleChoice extends ChoiceQuestion
{
    protected function correctCountProblem(int $correct): ?string
    {
        return $correct === 1 ? null : Texts::message('single_correct');
    }

    public function answer(array $payload, mixed $raw): ?array
    {
        $choice = is_array($raw) ? ($raw['choice'] ?? null) : null;

        return is_string($choice) && in_array($choice, $this->ids($payload), true) ? ['choice' => $choice] : null;
    }

    public function isCorrect(array $payload, array $answer): bool
    {
        return [$answer['choice'] ?? null] === $this->correctIds($payload);
    }

    public function solution(array $payload): array
    {
        return ['choice' => $this->correctIds($payload)[0] ?? null];
    }
}
