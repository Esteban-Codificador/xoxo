<?php

namespace App\Jobs;

use App\Domain\Content\Links\VerifyResourceLink;
use App\Models\ExternalResource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Verifies one resource URL off the request (the check can take seconds). */
class VerifyResourceLinkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public ExternalResource $resource) {}

    public function handle(VerifyResourceLink $verify): void
    {
        $verify->handle($this->resource);
    }
}
