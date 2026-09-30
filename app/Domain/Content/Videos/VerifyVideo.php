<?php

namespace App\Domain\Content\Videos;

use App\Models\Video;

/**
 * Checks a stored video with oEmbed and records the answer. The thumbnail
 * follows YouTube; title and channel stay as the editor left them. An
 * inconclusive check changes nothing.
 */
final readonly class VerifyVideo
{
    public function __construct(private YouTubeOEmbed $oembed) {}

    public function handle(Video $video): OEmbedResult
    {
        $result = $this->oembed->lookup($video->external_id);

        if ($result->status !== null) {
            // A check is not an editorial change: no audit row, no author.
            $video->forceFill([
                'link_status' => $result->status,
                'last_http_status' => $result->httpStatus,
                'last_checked_at' => now(),
                ...($result->isAvailable() ? ['thumbnail_url' => $result->thumbnailUrl] : []),
            ])->saveQuietly();
        }

        return $result;
    }
}
