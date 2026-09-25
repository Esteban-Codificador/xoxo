<?php

namespace App\Http\Resources;

use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Track
 */
class TrackSummaryResource extends JsonResource
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
            'position' => $this->position,
            'difficulty' => $this->difficulty->value,
            'estimated_hours' => $this->estimated_hours,
            'lessons_count' => (int) $this->getAttribute('lessons_count'),
        ];
    }
}
