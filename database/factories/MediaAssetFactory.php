<?php

namespace Database\Factories;

use App\Enums\MediaKind;
use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Rows only: tests that need the file store a real image with StoreMedia.
 *
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    public function definition(): array
    {
        $checksum = hash('sha256', fake()->unique()->uuid());

        return [
            'kind' => MediaKind::Image,
            'disk' => 'local',
            'path' => "media/{$checksum}.png",
            'original_name' => fake()->word().'.png',
            'mime_type' => 'image/png',
            'size_bytes' => fake()->numberBetween(1_000, 500_000),
            'width' => 1200,
            'height' => 800,
            'checksum' => $checksum,
        ];
    }
}
