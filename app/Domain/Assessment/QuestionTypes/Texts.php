<?php

namespace App\Domain\Assessment\QuestionTypes;

use Random\Randomizer;

/**
 * Checks shared by the question types: short plain texts (an option, an
 * item, a side of a pair) and shuffles that never keep the right order.
 */
final class Texts
{
    public const int MAX_LENGTH = 300;

    public const int MIN_ITEMS = 2;

    public const int MAX_ITEMS = 8;

    /**
     * A message of lang/{locale}/quizzes.php "payload".
     *
     * @param  array<string, int|string>  $params
     */
    public static function message(string $key, array $params = []): string
    {
        $message = __("quizzes.payload.{$key}", $params);

        return is_string($message) ? $message : $key;
    }

    /** The problem with one text, or null when it is fine. */
    public static function problem(mixed $text): ?string
    {
        if (! is_string($text) || trim($text) === '') {
            return self::message('text_required');
        }

        return mb_strlen(trim($text)) > self::MAX_LENGTH
            ? self::message('text_too_long', ['max' => self::MAX_LENGTH])
            : null;
    }

    public static function countProblem(int $count): ?string
    {
        return $count < self::MIN_ITEMS || $count > self::MAX_ITEMS
            ? self::message('count', ['min' => self::MIN_ITEMS, 'max' => self::MAX_ITEMS])
            : null;
    }

    /**
     * A shuffled copy that is never the given order (with two or more
     * elements): otherwise the page would show the answer.
     *
     * @template T
     *
     * @param  list<T>  $items
     * @return list<T>
     */
    public static function shuffleApart(array $items, Randomizer $random): array
    {
        if (count($items) < 2) {
            return $items;
        }

        /** @var list<T> $shuffled */
        $shuffled = $random->shuffleArray($items);

        return $shuffled === $items ? [...array_slice($items, 1), $items[0]] : $shuffled;
    }
}
