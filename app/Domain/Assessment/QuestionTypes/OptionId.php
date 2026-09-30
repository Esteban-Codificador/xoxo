<?php

namespace App\Domain\Assessment\QuestionTypes;

/**
 * The id of an option, item or side of a pair: a short hash of its text.
 * It is stable while the text stays the same, needs no storage, and tells
 * nothing about which option is right or where an item goes.
 */
final class OptionId
{
    public static function of(string $text): string
    {
        return substr(hash('xxh3', $text), 0, 10);
    }
}
