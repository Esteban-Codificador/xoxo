<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Module;
use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(2), '.');

        return [
            'track_id' => Track::factory(),
            'slug' => Str::slug($title),
            'title' => Str::title($title),
            'summary' => fake()->sentence(10),
            'position' => fake()->numberBetween(0, 20),
            'status' => ContentStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => ContentStatus::Published, 'published_at' => now()]);
    }
}
