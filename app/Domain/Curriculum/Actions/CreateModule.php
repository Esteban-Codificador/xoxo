<?php

namespace App\Domain\Curriculum\Actions;

use App\Enums\ContentStatus;
use App\Models\Module;
use App\Models\Track;

/**
 * A new module, as a draft at the end of its track.
 */
final class CreateModule
{
    /**
     * @param  array{title: string, slug: string, summary: string}  $data
     */
    public function handle(Track $track, array $data): Module
    {
        return $track->modules()->create([
            'title' => trim($data['title']),
            'slug' => $data['slug'],
            'summary' => trim($data['summary']),
            'position' => (int) $track->modules()->reorder()->max('position') + 1,
            'status' => ContentStatus::Draft,
        ]);
    }
}
