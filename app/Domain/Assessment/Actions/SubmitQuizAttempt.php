<?php

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\QuizGrader;
use App\Domain\Learning\ActivityRecorder;
use App\Domain\Learning\LessonMastery;
use App\Enums\ActivityType;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

/**
 * Grades an open attempt on the server. It grades the questions the
 * attempt showed that still exist, as they are now. Sending twice changes
 * nothing, and answers that arrive after the deadline (plus the grace
 * period) are not graded: the attempt fails as timed out.
 */
final readonly class SubmitQuizAttempt
{
    public function __construct(
        private QuizGrader $grader,
        private ExpireQuizAttempt $expire,
        private ActivityRecorder $activities,
        private LessonMastery $mastery,
    ) {}

    /**
     * @param  array<array-key, mixed>  $answers  raw answers by question id
     */
    public function handle(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $answers) {
            $attempt = QuizAttempt::query()->with(['quiz.lesson', 'user'])->lockForUpdate()->findOrFail($attempt->id);

            if (! $attempt->isOpen()) {
                return $attempt;
            }

            if ($attempt->isOverdue()) {
                return $this->expire->handle($attempt);
            }

            $quiz = $attempt->quiz;
            $order = array_flip($attempt->questions);
            $questions = QuizQuestion::query()->whereBelongsTo($quiz)->whereIn('id', $attempt->questions)->get()
                ->sortBy(fn (QuizQuestion $question) => $order[$question->id])
                ->values();

            $graded = $this->grader->grade($questions, $answers, $quiz->pass_threshold);

            foreach ($graded->answers as $answer) {
                QuizAttemptAnswer::query()->create([
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $answer->questionId,
                    'answer' => $answer->answer,
                    'is_correct' => $answer->correct,
                    'points_awarded' => $answer->points,
                ]);
            }

            $attempt->update([
                'submitted_at' => now(),
                'score' => $graded->score,
                'points_earned' => $graded->pointsEarned,
                'points_total' => $graded->pointsTotal,
                'passed' => $graded->passed,
            ]);

            $this->activities->record($attempt->user, $graded->passed ? ActivityType::QuizPassed : ActivityType::QuizFailed, $quiz, [
                'attempt' => $attempt->attempt_number,
                'score' => $graded->score,
            ]);

            $progress = LessonProgress::query()->whereBelongsTo($attempt->user)->whereBelongsTo($quiz->lesson)->lockForUpdate()->first();

            if ($progress !== null && $graded->passed) {
                $this->mastery->promote($attempt->user, $quiz->lesson, $progress);
            }

            return $attempt;
        });
    }
}
