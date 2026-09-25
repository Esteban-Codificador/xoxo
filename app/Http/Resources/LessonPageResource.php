<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

/**
 * What a learner reads: the published version. The working copy may hold
 * unpublished edits and never reaches this page (ADR-006).
 *
 * @mixin Lesson
 */
class LessonPageResource extends JsonResource
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
            'why_it_matters' => $version->why_it_matters,
            'learning_objectives' => $version->learning_objectives,
            'content_type' => $version->content_type->value,
            'difficulty' => $version->difficulty->value,
            'estimated_minutes' => $version->estimated_minutes,
            'body' => $version->body,
            'version' => $version->version,
            'published_at' => $version->published_at->toIso8601String(),
        ];
    }
}
