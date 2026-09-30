<?php

namespace App\Domain\Assessment\QuestionTypes;

use Random\Randomizer;

/**
 * Payload: {options: [{text, correct}]}, 2–8 options with distinct texts.
 * Options keep the author's order ("none of the above" stays last).
 */
abstract class ChoiceQuestion implements QuestionTypeHandler
{
    /** The problem with how many options are marked correct, or null. */
    abstract protected function correctCountProblem(int $correct): ?string;

    public function problems(mixed $payload): array
    {
        $options = is_array($payload) ? ($payload['options'] ?? null) : null;

        if (! is_array($options) || ! array_is_list($options)) {
            return ['options' => (string) Texts::countProblem(0)];
        }

        $problems = [];
        $count = Texts::countProblem(count($options));

        if ($count !== null) {
            $problems['options'] = $count;
        }

        $seen = [];
        $correct = 0;
        foreach ($options as $index => $option) {
            $text = is_array($option) ? ($option['text'] ?? null) : null;
            $problem = Texts::problem($text);

            if ($problem === null && in_array(trim((string) $text), $seen, true)) {
                $problem = Texts::message('duplicate');
            }

            if ($problem !== null) {
                $problems["options.{$index}.text"] = $problem;
            } else {
                $seen[] = trim((string) $text);
            }

            if (! is_array($option) || ! is_bool($option['correct'] ?? null)) {
                $problems["options.{$index}.correct"] = Texts::message('invalid');
            } elseif ($option['correct']) {
                $correct++;
            }
        }

        if ($problems === []) {
            $problem = $this->correctCountProblem($correct);

            if ($problem !== null) {
                $problems['options'] = $problem;
            }
        }

        return $problems;
    }

    public function normalize(array $payload): array
    {
        return ['options' => array_map(fn (array $option) => [
            'text' => trim((string) $option['text']),
            'correct' => (bool) $option['correct'],
        ], $this->options($payload))];
    }

    public function present(array $payload, Randomizer $random): array
    {
        return ['options' => array_map(fn (array $option) => [
            'id' => OptionId::of($option['text']),
            'text' => $option['text'],
        ], $this->options($payload))];
    }

    /**
     * Ids of the right options, in the author's order.
     *
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    protected function correctIds(array $payload): array
    {
        $ids = [];
        foreach ($this->options($payload) as $option) {
            if ($option['correct']) {
                $ids[] = OptionId::of($option['text']);
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    protected function ids(array $payload): array
    {
        return array_map(fn (array $option) => OptionId::of($option['text']), $this->options($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{text: string, correct: bool}>
     */
    protected function options(array $payload): array
    {
        /** @var list<array{text: string, correct: bool}> */
        return $payload['options'] ?? [];
    }
}
