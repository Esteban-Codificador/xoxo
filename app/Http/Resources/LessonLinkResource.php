<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

/**
 * A link to a lesson with its published title (never the working copy).
 *
 * @mixin Lesson
 */
class LessonLinkResource extends JsonResource
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
        ];
    }
}
