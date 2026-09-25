<?php

namespace App\Domain\Content\Links;

use App\Enums\LinkStatus;

final readonly class LinkCheckResult
{
    /**
     * @param  LinkStatus|null  $status  Null when the check was inconclusive (timeouts, 5xx, bot protection).
     */
    public function __construct(
        public string $url,
        public ?LinkStatus $status,
        public ?int $httpStatus = null,
        public ?string $finalUrl = null,
        public ?string $error = null,
    ) {}

    public function isBroken(): bool
    {
        return $this->status === LinkStatus::Broken;
    }
}
