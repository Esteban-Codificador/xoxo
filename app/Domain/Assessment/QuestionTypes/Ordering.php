<?php

namespace App\Domain\Assessment\QuestionTypes;

use Random\Randomizer;

/**
 * Payload: {items: [text]} in the right order, 2–8 distinct items. The
 * learner gets them shuffled. Answer: {order: [id]}, all of them.
 */
final class Ordering implements QuestionTypeHandler
{
    public function problems(mixed $payload): array
    {
        $items = is_array($payload) ? ($payload['items'] ?? null) : null;

        if (! is_array($items) || ! array_is_list($items)) {
            return ['items' => (string) Texts::countProblem(0)];
        }

        $problems = [];
        $count = Texts::countProblem(count($items));

        if ($count !== null) {
            $problems['items'] = $count;
        }

        $seen = [];
        foreach ($items as $index => $item) {
            $problem = Texts::problem($item);

            if ($problem === null && in_array(trim((string) $item), $seen, true)) {
                $problem = Texts::message('duplicate');
            }

            if ($problem !== null) {
                $problems["items.{$index}"] = $problem;
            } else {
                $seen[] = trim((string) $item);
            }
        }

        return $problems;
    }

    public function normalize(array $payload): array
    {
        return ['items' => array_map(fn (mixed $item) => trim((string) $item), $this->items($payload))];
    }

    public function present(array $payload, Randomizer $random): array
    {
        $items = array_map(fn (string $item) => ['id' => OptionId::of($item), 'text' => $item], $this->items($payload));

        return ['items' => Texts::shuffleApart($items, $random)];
    }

    public function answer(array $payload, mixed $raw): ?array
    {
        $order = is_array($raw) ? ($raw['order'] ?? null) : null;

        if (! is_array($order) || ! array_is_list($order)) {
            return null;
        }

        $ids = $this->ids($payload);
        $given = $order;
        sort($ids);
        sort($given);

        return $given === $ids ? ['order' => $order] : null;
    }

    public function isCorrect(array $payload, array $answer): bool
    {
        return ($answer['order'] ?? null) === $this->ids($payload);
    }

    public function solution(array $payload): array
    {
        return ['order' => $this->ids($payload)];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function ids(array $payload): array
    {
        return array_map(fn (string $item) => OptionId::of($item), $this->items($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function items(array $payload): array
    {
        /** @var list<string> */
        return $payload['items'] ?? [];
    }
}
