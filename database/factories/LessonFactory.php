<?php

namespace Database\Factories;

use App\Domain\Content\RichContent\RichContent;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\Difficulty;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'module_id' => Module::factory(),
            'slug' => Str::slug($title),
            'title' => rtrim($title, '.'),
            'summary' => fake()->sentence(20),
            'why_it_matters' => fake()->sentence(20),
            'learning_objectives' => [fake()->sentence(8), fake()->sentence(8)],
            'body' => self::body([fake()->paragraph(), fake()->paragraph(), fake()->paragraph()]),
            'content_type' => ContentType::Concept,
            'difficulty' => Difficulty::Beginner,
            'estimated_minutes' => 30,
            'position' => fake()->numberBetween(0, 20),
            'status' => ContentStatus::Draft,
        ];
    }

    /**
     * @param  list<string>  $paragraphs
     */
    public static function body(array $paragraphs): RichContent
    {
        return RichContent::fromDocument([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Concepto']]],
                ...array_map(fn (string $text) => ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]], $paragraphs),
            ],
        ]);
    }
}
