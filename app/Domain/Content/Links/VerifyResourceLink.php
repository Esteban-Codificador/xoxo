<?php

namespace App\Domain\Content\Links;

use App\Models\ExternalResource;

/**
 * Checks the URL of a stored resource and records the result on it. An
 * inconclusive check (timeout, 5xx, bot protection) leaves the previous
 * result untouched: it proves nothing either way.
 */
final readonly class VerifyResourceLink
{
    public function __construct(private LinkChecker $checker) {}

    public function handle(ExternalResource $resource): LinkCheckResult
    {
        $result = $this->checker->check($resource->url);

        if ($result->status !== null) {
            // A link check is not an editorial change: no audit row, no author.
            $resource->forceFill([
                'link_status' => $result->status,
                'last_http_status' => $result->httpStatus,
                'last_checked_at' => now(),
            ])->saveQuietly();
        }

        return $result;
    }
}
