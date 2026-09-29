<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Content\RichContent\RichContent;
use App\Enums\Difficulty;
use App\Models\Track;

final class UpdateTrack
{
    /**
     * Tracks are not versioned: learners see the change right away.
     *
     * @param  array{title: string, slug: string, summary: string, why_it_matters: string, description: array<string, mixed>|null, difficulty: string, estimated_hours: int|string|null}  $data
     */
    public function handle(Track $track, array $data): Track
    {
        $track->forceFill([
            'title' => trim($data['title']),
            'slug' => $data['slug'],
            'summary' => trim($data['summary']),
            'why_it_matters' => trim($data['why_it_matters']),
            'description' => $data['description'] === null ? null : RichContent::fromArray($data['description']),
            'difficulty' => Difficulty::from($data['difficulty']),
            'estimated_hours' => $data['estimated_hours'] === null ? null : (int) $data['estimated_hours'],
        ])->save();

        return $track;
    }
}
