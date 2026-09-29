<?php

namespace App\Domain\Curriculum\Actions;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Models\Roadmap;
use App\Models\Track;

/**
 * A new track, as a draft at the end of its roadmap. Prerequisites,
 * description and modules are added from its editor.
 */
final class CreateTrack
{
    /**
     * @param  array{title: string, slug: string, summary: string, why_it_matters: string, difficulty: string, estimated_hours: int|string|null}  $data
     */
    public function handle(Roadmap $roadmap, array $data): Track
    {
        return $roadmap->tracks()->create([
            'title' => trim($data['title']),
            'slug' => $data['slug'],
            'summary' => trim($data['summary']),
            'why_it_matters' => trim($data['why_it_matters']),
            'difficulty' => Difficulty::from($data['difficulty']),
            'estimated_hours' => $data['estimated_hours'] === null ? null : (int) $data['estimated_hours'],
            'position' => (int) $roadmap->tracks()->reorder()->max('position') + 1,
            'status' => ContentStatus::Draft,
        ]);
    }
}
