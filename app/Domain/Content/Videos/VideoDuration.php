<?php

namespace App\Domain\Content\Videos;

/**
 * Durations as people write them ("12:34", "1:02:03"). oEmbed does not
 * give the duration, so the editor types it.
 */
final class VideoDuration
{
    public const string PATTERN = '/^(?:(\d{1,2}):)?([0-5]?\d):([0-5]\d)$/';

    public static function parse(?string $value): ?int
    {
        if ($value === null || trim($value) === '' || preg_match(self::PATTERN, trim($value), $match) !== 1) {
            return null;
        }

        $seconds = ((int) $match[1]) * 3600 + ((int) $match[2]) * 60 + (int) $match[3];

        return $seconds > 0 ? $seconds : null;
    }

    public static function format(?int $seconds): ?string
    {
        if ($seconds === null || $seconds <= 0) {
            return null;
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $rest = $seconds % 60;

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $rest)
            : sprintf('%d:%02d', $minutes, $rest);
    }
}
