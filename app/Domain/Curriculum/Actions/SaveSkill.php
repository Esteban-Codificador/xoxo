<?php

namespace App\Domain\Curriculum\Actions;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Models\Skill;

final class SaveSkill
{
    /**
     * @param  array{name: string, slug: string, description: string, difficulty: string}  $data
     */
    public function handle(?Skill $skill, array $data): Skill
    {
        $skill ??= new Skill(['status' => ContentStatus::Draft]);

        $skill->forceFill([
            'name' => trim($data['name']),
            'slug' => $data['slug'],
            'description' => trim($data['description']),
            'difficulty' => Difficulty::from($data['difficulty']),
        ])->save();

        return $skill;
    }
}
