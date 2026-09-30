<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\LinkStatus;
use App\Enums\VideoProvider;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => VideoProvider::Youtube,
            // "fake-" plus 6 characters: a valid ID shape that no real video uses on purpose.
            'external_id' => 'fake-'.Str::random(6),
            'title' => rtrim(fake()->sentence(5), '.'),
            'instructor' => fake()->company(),
            'thumbnail_url' => null,
            'language' => 'en',
            'link_status' => LinkStatus::Ok,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ];
    }

    public function unchecked(): static
    {
        return $this->state(['link_status' => LinkStatus::Unchecked]);
    }
}
