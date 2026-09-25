<?php

namespace App\Http\Resources;

use App\Models\Roadmap;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Roadmap
 */
class RoadmapSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
        ];
    }
}
