<?php

namespace App\Http\Resources;

use App\Domain\Content\Videos\VideoDuration;
use App\Models\Video;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A video a lesson recommends: the provider's ID (the page builds the
 * embed URL), what oEmbed and the editor say about it, and why this lesson
 * links it.
 *
 * @mixin Video
 */
class VideoLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $link = $this->resource->relationLoaded('pivot') ? $this->resource->getRelation('pivot') : null;

        return [
            'id' => $this->id,
            'provider' => $this->provider->value,
            'video_id' => $this->external_id,
            'title' => $this->title,
            'instructor' => $this->instructor,
            'description' => $this->description,
            'duration' => VideoDuration::format($this->duration_seconds),
            'language' => $this->language,
            'thumbnail_url' => $this->thumbnail_url,
            'note' => $link instanceof Pivot ? $link->getAttribute('note') : null,
            'start_seconds' => $link instanceof Pivot ? $link->getAttribute('start_seconds') : null,
        ];
    }
}
