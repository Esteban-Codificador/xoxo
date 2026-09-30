<?php

namespace Database\Factories;

use App\Domain\Content\RichContent\RichContent;
use App\Enums\QuestionType;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A single choice question by default; one state per type, each with a
 * known right answer for the tests.
 *
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'type' => QuestionType::SingleChoice,
            'prompt' => self::text(rtrim(fake()->sentence(8), '.').'?'),
            'payload' => ['options' => [
                ['text' => 'Correcta', 'correct' => true],
                ['text' => 'Incorrecta', 'correct' => false],
                ['text' => 'Tampoco', 'correct' => false],
            ]],
            'explanation' => self::text(fake()->sentence(12)),
            'difficulty' => null,
            'points' => 1,
            'position' => 0,
        ];
    }

    public function multipleChoice(): static
    {
        return $this->state(['type' => QuestionType::MultipleChoice, 'payload' => ['options' => [
            ['text' => 'Primera correcta', 'correct' => true],
            ['text' => 'Incorrecta', 'correct' => false],
            ['text' => 'Segunda correcta', 'correct' => true],
        ]]]);
    }

    public function trueFalse(bool $answer = true): static
    {
        return $this->state(['type' => QuestionType::TrueFalse, 'payload' => ['answer' => $answer]]);
    }

    public function ordering(): static
    {
        return $this->state(['type' => QuestionType::Ordering, 'payload' => ['items' => ['Primero', 'Segundo', 'Tercero']]]);
    }

    public function matching(): static
    {
        return $this->state(['type' => QuestionType::Matching, 'payload' => ['pairs' => [
            ['left' => 'git add', 'right' => 'Prepara cambios'],
            ['left' => 'git commit', 'right' => 'Guarda una instantánea'],
            ['left' => 'git push', 'right' => 'Publica en el remoto'],
        ]]]);
    }

    public static function text(string $text): RichContent
    {
        return RichContent::fromDocument(['type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]],
        ]]);
    }
}
