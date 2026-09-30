<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'title' => rtrim(fake()->sentence(4), '.'),
            'description' => null,
            'pass_threshold' => 70,
            'time_limit_seconds' => null,
            'max_attempts' => null,
            'shuffle_questions' => false,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ContentStatus::Draft, 'published_at' => null]);
    }
}
