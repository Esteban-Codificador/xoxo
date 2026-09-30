<?php

namespace App\Jobs;

use App\Domain\Content\Videos\VerifyVideo;
use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Checks one video with oEmbed off the request. */
class VerifyVideoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Video $video) {}

    public function handle(VerifyVideo $verify): void
    {
        $verify->handle($this->video);
    }
}
