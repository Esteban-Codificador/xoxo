<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\LinkStatus;
use App\Enums\ResourceType;
use App\Models\ExternalResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalResource>
 */
class ExternalResourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            // example.test is reserved (RFC 2606): factory data never points to a real site.
            'url' => 'https://docs.example.test/'.fake()->unique()->slug(3),
            'type' => ResourceType::Documentation,
            'provider' => fake()->company(),
            'description' => fake()->sentence(12),
            'language' => 'en',
            'is_official' => true,
            'link_status' => LinkStatus::Unchecked,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ];
    }
}
