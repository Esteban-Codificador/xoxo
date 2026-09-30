<?php

namespace App\Domain\Content\Videos;

use App\Enums\LinkStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Asks YouTube's oEmbed endpoint about a video (content-architecture §8):
 * no API key, and the same answer the embedded player would get. A 404 is
 * a video that does not exist; 401 and 403, one that is private or cannot
 * be embedded, so learners could not watch it either. Anything else proves
 * nothing and leaves the video as it was.
 */
final class YouTubeOEmbed
{
    private const string ENDPOINT = 'https://www.youtube.com/oembed';

    /** Thumbnails are only taken from YouTube's image host. */
    private const string THUMBNAIL_HOST = 'i.ytimg.com';

    public function __construct(private readonly int $timeoutSeconds = 10) {}

    public function lookup(string $videoId): OEmbedResult
    {
        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'AI-Engineer-Roadmap-VideoChecker/1.0 (+content verification)'])
                // Retry what may be transient; a 4xx is an answer.
                ->retry(2, 500, fn (Throwable $exception) => ! $exception instanceof RequestException || $exception->response->serverError(), throw: false)
                ->get(self::ENDPOINT, ['url' => "https://www.youtube.com/watch?v={$videoId}", 'format' => 'json']);
        } catch (ConnectionException $exception) {
            return new OEmbedResult($videoId, null, reason: $exception->getMessage());
        }

        $status = $response->status();

        return match (true) {
            $status === 404, $status === 400 => new OEmbedResult($videoId, LinkStatus::Broken, $status, reason: 'el video no existe'),
            $status === 401, $status === 403 => new OEmbedResult($videoId, LinkStatus::Broken, $status, reason: 'el video es privado o no permite insertarlo'),
            $response->successful() && is_string($response->json('title')) => new OEmbedResult(
                $videoId,
                LinkStatus::Ok,
                $status,
                title: $this->text($response->json('title'), 200),
                author: $this->text($response->json('author_name'), 160),
                thumbnailUrl: $this->thumbnail($response->json('thumbnail_url')),
            ),
            default => new OEmbedResult($videoId, null, $status, reason: "HTTP {$status}"),
        };
    }

    private function text(mixed $value, int $max): ?string
    {
        return is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, $max) : null;
    }

    private function thumbnail(mixed $url): ?string
    {
        if (! is_string($url) || strlen($url) > 2048) {
            return null;
        }

        $parts = parse_url($url);

        return is_array($parts) && ($parts['scheme'] ?? null) === 'https' && ($parts['host'] ?? null) === self::THUMBNAIL_HOST ? $url : null;
    }
}
