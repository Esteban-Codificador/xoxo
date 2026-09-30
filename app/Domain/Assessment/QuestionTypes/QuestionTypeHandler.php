<?php

namespace App\Domain\Assessment\QuestionTypes;

use Random\Randomizer;

/**
 * One question type (ADR-022): the only place that knows its payload, how
 * a learner answers it and whether the answer is right. Quizzes and, later,
 * exercises grade through it.
 *
 * Options, items and pairs are identified by an id derived from their text
 * (OptionId), so the page never says which one is right or where it goes,
 * and the stored payload holds only what the author wrote.
 */
interface QuestionTypeHandler
{
    /**
     * What is wrong with an author's payload, by path inside it ("options",
     * "items.2"); empty when it is valid.
     *
     * @return array<string, string>
     */
    public function problems(mixed $payload): array;

    /**
     * The payload as stored: known keys only, texts trimmed. Only for a
     * payload without problems.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalize(array $payload): array;

    /**
     * What the learner sees, without the answer. Ordering and matching
     * shuffle with the given randomizer, seeded per attempt and question.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function present(array $payload, Randomizer $random): array;

    /**
     * The learner's answer in canonical form, or null when it is missing or
     * does not fit the question (it counts as not answered).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function answer(array $payload, mixed $raw): ?array;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $answer  canonical, from answer()
     */
    public function isCorrect(array $payload, array $answer): bool;

    /**
     * The right answer, shaped like answer().
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function solution(array $payload): array;
}
