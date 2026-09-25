<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(2), '.');

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'description' => fake()->sentence(15),
            'difficulty' => Difficulty::Beginner,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ];
    }
}
