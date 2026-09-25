<?php

namespace App\Http\Resources;

use App\Models\ExternalResource;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An external resource recommended by a lesson or track.
 *
 * @mixin ExternalResource
 */
class ResourceLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $link = $this->resource->relationLoaded('pivot') ? $this->resource->getRelation('pivot') : null;

        return [
            'title' => $this->title,
            'url' => $this->url,
            'type' => $this->type->value,
            'provider' => $this->provider,
            'description' => $this->description,
            'language' => $this->language,
            'is_official' => $this->is_official,
            'link_status' => $this->link_status->value,
            // Why this lesson recommends it (resource_links.note), when loaded through a link.
            'note' => $link instanceof Pivot ? $link->getAttribute('note') : null,
        ];
    }
}
