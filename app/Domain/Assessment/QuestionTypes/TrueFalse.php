<?php

namespace App\Domain\Assessment\QuestionTypes;

use Random\Randomizer;

/** Payload: {answer: bool}. Answer: {value: bool}. */
final class TrueFalse implements QuestionTypeHandler
{
    public function problems(mixed $payload): array
    {
        return is_array($payload) && is_bool($payload['answer'] ?? null) ? [] : ['answer' => Texts::message('true_false')];
    }

    public function normalize(array $payload): array
    {
        return ['answer' => (bool) $payload['answer']];
    }

    public function present(array $payload, Randomizer $random): array
    {
        return [];
    }

    public function answer(array $payload, mixed $raw): ?array
    {
        $value = is_array($raw) ? ($raw['value'] ?? null) : null;

        return is_bool($value) ? ['value' => $value] : null;
    }

    public function isCorrect(array $payload, array $answer): bool
    {
        return ($answer['value'] ?? null) === $payload['answer'];
    }

    public function solution(array $payload): array
    {
        return ['value' => (bool) $payload['answer']];
    }
}
