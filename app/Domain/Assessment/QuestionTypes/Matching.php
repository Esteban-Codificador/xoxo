<?php

namespace App\Domain\Assessment\QuestionTypes;

use Random\Randomizer;

/**
 * Payload: {pairs: [{left, right}]}, 2–8 pairs; lefts distinct and rights
 * distinct. The learner gets the lefts in order and the rights shuffled.
 * Answer: {matches: {leftId: rightId}}; right only when every pair is.
 */
final class Matching implements QuestionTypeHandler
{
    public function problems(mixed $payload): array
    {
        $pairs = is_array($payload) ? ($payload['pairs'] ?? null) : null;

        if (! is_array($pairs) || ! array_is_list($pairs)) {
            return ['pairs' => (string) Texts::countProblem(0)];
        }

        $problems = [];
        $count = Texts::countProblem(count($pairs));

        if ($count !== null) {
            $problems['pairs'] = $count;
        }

        $seen = ['left' => [], 'right' => []];
        foreach ($pairs as $index => $pair) {
            foreach (['left', 'right'] as $side) {
                $text = is_array($pair) ? ($pair[$side] ?? null) : null;
                $problem = Texts::problem($text);

                if ($problem === null && in_array(trim((string) $text), $seen[$side], true)) {
                    $problem = Texts::message('duplicate');
                }

                if ($problem !== null) {
                    $problems["pairs.{$index}.{$side}"] = $problem;
                } else {
                    $seen[$side][] = trim((string) $text);
                }
            }
        }

        return $problems;
    }

    public function normalize(array $payload): array
    {
        return ['pairs' => array_map(fn (array $pair) => [
            'left' => trim((string) $pair['left']),
            'right' => trim((string) $pair['right']),
        ], $this->pairs($payload))];
    }

    public function present(array $payload, Randomizer $random): array
    {
        $pairs = $this->pairs($payload);

        return [
            'left' => array_map(fn (array $pair) => ['id' => OptionId::of($pair['left']), 'text' => $pair['left']], $pairs),
            'right' => Texts::shuffleApart(
                array_map(fn (array $pair) => ['id' => OptionId::of($pair['right']), 'text' => $pair['right']], $pairs),
                $random,
            ),
        ];
    }

    public function answer(array $payload, mixed $raw): ?array
    {
        $matches = is_array($raw) ? ($raw['matches'] ?? null) : null;

        if (! is_array($matches)) {
            return null;
        }

        $rights = array_map(fn (array $pair) => OptionId::of($pair['right']), $this->pairs($payload));
        $given = [];
        foreach ($this->pairs($payload) as $pair) {
            $left = OptionId::of($pair['left']);
            $right = $matches[$left] ?? null;

            if (is_string($right) && in_array($right, $rights, true)) {
                $given[$left] = $right;
            }
        }

        return $given === [] ? null : ['matches' => $given];
    }

    public function isCorrect(array $payload, array $answer): bool
    {
        return ($answer['matches'] ?? null) === $this->solution($payload)['matches'];
    }

    public function solution(array $payload): array
    {
        $matches = [];
        foreach ($this->pairs($payload) as $pair) {
            $matches[OptionId::of($pair['left'])] = OptionId::of($pair['right']);
        }

        return ['matches' => $matches];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{left: string, right: string}>
     */
    private function pairs(array $payload): array
    {
        /** @var list<array{left: string, right: string}> */
        return $payload['pairs'] ?? [];
    }
}
