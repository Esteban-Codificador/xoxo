<?php

namespace App\Models;

use App\Casts\AsRichContent;
use App\Domain\Assessment\QuestionTypes\QuestionTypeHandler;
use App\Domain\Assessment\QuestionTypes\QuestionTypes;
use App\Domain\Content\RichContent\RichContent;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use Database\Factories\QuizQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A question of a quiz. The payload is what the author wrote, answer
 * included: it never reaches a learner's page before grading (QuestionTypeHandler::present).
 *
 * @property int $id
 * @property int $quiz_id
 * @property QuestionType $type
 * @property RichContent $prompt
 * @property array<string, mixed> $payload
 * @property RichContent $explanation
 * @property Difficulty|null $difficulty
 * @property int $points
 * @property int $position
 */
#[Fillable(['quiz_id', 'type', 'prompt', 'payload', 'explanation', 'difficulty', 'points', 'position'])]
class QuizQuestion extends Model
{
    /** @use HasFactory<QuizQuestionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'prompt' => AsRichContent::class,
            'payload' => 'array',
            'explanation' => AsRichContent::class,
            'difficulty' => Difficulty::class,
            'points' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function handler(): QuestionTypeHandler
    {
        return QuestionTypes::for($this->type);
    }
}
