<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How one question of a submitted attempt was answered and graded. A row
 * per question, so the most failed questions are a GROUP BY.
 *
 * @property int $id
 * @property int $quiz_attempt_id
 * @property int $quiz_question_id
 * @property array<string, mixed>|null $answer canonical (QuestionTypeHandler::answer); null when not answered
 * @property bool $is_correct
 * @property int $points_awarded
 */
#[Fillable(['quiz_attempt_id', 'quiz_question_id', 'answer', 'is_correct', 'points_awarded'])]
class QuizAttemptAnswer extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'answer' => 'array',
            'is_correct' => 'boolean',
            'points_awarded' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    /**
     * @return BelongsTo<QuizQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
