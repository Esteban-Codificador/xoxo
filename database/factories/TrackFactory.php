<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Models\Roadmap;
use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Track>
 */
class TrackFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(2), '.');

        return [
            'roadmap_id' => Roadmap::factory(),
            'slug' => Str::slug($title),
            'title' => Str::title($title),
            'summary' => fake()->sentence(12),
            'why_it_matters' => fake()->sentence(16),
            'position' => fake()->numberBetween(0, 20),
            'difficulty' => Difficulty::Beginner,
            'status' => ContentStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => ContentStatus::Published, 'published_at' => now()]);
    }
}
