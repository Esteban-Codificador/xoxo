<?php

namespace App\Domain\Content\Videos;

use App\Domain\Content\RichContent\RichContentSchema;

/**
 * The video ID in what an editor pastes: a bare ID or a youtube.com /
 * youtu.be link. The same rules as the editor (youtube.ts); anything else
 * is rejected rather than guessed.
 */
final class YouTubeId
{
    private const array HOSTS = ['youtube.com', 'youtube-nocookie.com'];

    public static function parse(string $input): ?string
    {
        $value = trim($input);

        if (preg_match(RichContentSchema::YOUTUBE_ID_PATTERN, $value) === 1) {
            return $value;
        }

        $parts = parse_url($value);

        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true)) {
            return null;
        }

        $host = (string) preg_replace('/^(www|m)\./', '', strtolower($parts['host'] ?? ''));
        $path = $parts['path'] ?? '';
        $candidate = null;

        if ($host === 'youtu.be') {
            $candidate = explode('/', $path)[1] ?? null;
        } elseif (in_array($host, self::HOSTS, true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $candidate = is_string($query['v'] ?? null) ? $query['v'] : null;
            } elseif (preg_match('#^/(?:embed|shorts|live|v)/([^/]+)#', $path, $match) === 1) {
                $candidate = $match[1];
            }
        }

        return is_string($candidate) && preg_match(RichContentSchema::YOUTUBE_ID_PATTERN, $candidate) === 1 ? $candidate : null;
    }
}
