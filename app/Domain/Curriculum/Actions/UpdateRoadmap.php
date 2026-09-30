<?php

namespace App\Domain\Curriculum\Actions;

use App\Domain\Content\RichContent\RichContent;
use App\Enums\UnlockPolicy;
use App\Models\Roadmap;

final class UpdateRoadmap
{
    /**
     * Roadmaps are not versioned: learners see the change right away, and
     * a new unlock policy applies to their next request (ADR-009).
     *
     * @param  array{title: string, slug: string, summary: string, description: array<string, mixed>|null, unlock_policy: string}  $data
     */
    public function handle(Roadmap $roadmap, array $data): Roadmap
    {
        $roadmap->forceFill([
            'title' => trim($data['title']),
            'slug' => $data['slug'],
            'summary' => trim($data['summary']),
            'description' => $data['description'] === null ? null : RichContent::fromArray($data['description']),
            'unlock_policy' => UnlockPolicy::from($data['unlock_policy']),
        ])->save();

        return $roadmap;
    }
}
