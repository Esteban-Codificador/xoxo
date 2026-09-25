<?php

namespace App\Http\Resources;

use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Track
 */
class TrackDetailResource extends JsonResource
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
            'why_it_matters' => $this->why_it_matters,
            'description' => $this->description,
            'position' => $this->position,
            'difficulty' => $this->difficulty->value,
            'estimated_hours' => $this->estimated_hours,
        ];
    }
}
