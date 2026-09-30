<?php

namespace App\Domain\Content\Videos;

use App\Enums\LinkStatus;

/**
 * What oEmbed says about a video. `status` is null when the answer proves
 * nothing (timeout, 5xx, rate limit): the video may well exist.
 */
final readonly class OEmbedResult
{
    public function __construct(
        public string $videoId,
        public ?LinkStatus $status,
        public ?int $httpStatus = null,
        public ?string $title = null,
        public ?string $author = null,
        public ?string $thumbnailUrl = null,
        public ?string $reason = null,
    ) {}

    public function isAvailable(): bool
    {
        return $this->status === LinkStatus::Ok;
    }

    public function isBroken(): bool
    {
        return $this->status === LinkStatus::Broken;
    }
}
