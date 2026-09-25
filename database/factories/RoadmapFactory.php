<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\UnlockPolicy;
use App\Models\Roadmap;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Roadmap>
 */
class RoadmapFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(3), '.');

        return [
            'slug' => Str::slug($title),
            'title' => Str::title($title),
            'summary' => fake()->sentence(12),
            'locale' => 'es',
            'unlock_policy' => UnlockPolicy::Advisory,
            'mastery_threshold' => 90,
            'status' => ContentStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => ContentStatus::Published, 'published_at' => now()]);
    }
}
