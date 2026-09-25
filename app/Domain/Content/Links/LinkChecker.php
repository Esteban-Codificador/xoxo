<?php

namespace App\Domain\Content\Links;

use App\Enums\LinkStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Checks that an external resource URL still exists (content-architecture §8).
 * Only 404 and 410 count as broken: a timeout, a 5xx or a bot wall is
 * inconclusive and must not block a merge because a third-party site is down.
 */
final class LinkChecker
{
    private const array BROKEN_STATUSES = [404, 410];

    /** Some servers reject HEAD; retry those with GET. */
    private const array RETRY_WITH_GET = [403, 405, 501];

    public function __construct(private readonly int $timeoutSeconds = 15) {}

    public function check(string $url): LinkCheckResult
    {
        try {
            $response = $this->request()->head($url);

            if (in_array($response->status(), self::RETRY_WITH_GET, true)) {
                $response = $this->request()->get($url);
            }
        } catch (ConnectionException $exception) {
            return new LinkCheckResult($url, null, error: $exception->getMessage());
        }

        return $this->result($url, $response);
    }

    private function request(): PendingRequest
    {
        return Http::timeout($this->timeoutSeconds)
            ->withHeaders(['User-Agent' => 'AI-Engineer-Roadmap-LinkChecker/1.0 (+content verification)'])
            ->retry(2, 500, throw: false);
    }

    private function result(string $url, Response $response): LinkCheckResult
    {
        $status = $response->status();
        $finalUrl = (string) ($response->effectiveUri() ?? $url);

        if (in_array($status, self::BROKEN_STATUSES, true)) {
            return new LinkCheckResult($url, LinkStatus::Broken, $status, $finalUrl);
        }

        if (! $response->successful()) {
            return new LinkCheckResult($url, null, $status, $finalUrl, "HTTP {$status}");
        }

        $redirected = rtrim($finalUrl, '/') !== rtrim($url, '/');

        return new LinkCheckResult($url, $redirected ? LinkStatus::Redirected : LinkStatus::Ok, $status, $finalUrl);
    }
}
