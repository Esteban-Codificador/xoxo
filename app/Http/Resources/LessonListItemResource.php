<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

/**
 * A lesson in a track outline, from its published version.
 *
 * @mixin Lesson
 */
class LessonListItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $version = $this->publishedVersion
            ?? throw new LogicException("Lesson {$this->slug} has no published version.");

        return [
            'slug' => $this->slug,
            'title' => $version->title,
            'summary' => $version->summary,
            'content_type' => $version->content_type->value,
            'difficulty' => $version->difficulty->value,
            'estimated_minutes' => $version->estimated_minutes,
        ];
    }
}
