<?php

namespace App\Domain\Assessment\Actions;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Random\Randomizer;

/**
 * Opens an attempt, or resumes the open one: reloading the page or
 * clicking twice never costs an attempt. The attempt fixes its questions
 * and their order, and its deadline, when it starts.
 */
final readonly class StartQuizAttempt
{
    public function __construct(private ExpireQuizAttempt $expire) {}

    /**
     * @throws ValidationException when no attempts are left or the quiz has no questions
     */
    public function handle(User $user, Quiz $quiz): QuizAttempt
    {
        try {
            return DB::transaction(function () use ($user, $quiz) {
                $open = QuizAttempt::query()->whereBelongsTo($user)->whereBelongsTo($quiz)
                    ->whereNull('submitted_at')->lockForUpdate()->first();

                if ($open !== null && ! $open->isOverdue()) {
                    return $open;
                }

                if ($open !== null) {
                    $this->expire->handle($open);
                }

                $used = (int) QuizAttempt::query()->whereBelongsTo($user)->whereBelongsTo($quiz)->max('attempt_number');

                if ($quiz->max_attempts !== null && $used >= $quiz->max_attempts) {
                    throw ValidationException::withMessages(['quiz' => __('quizzes.no_attempts_left')]);
                }

                /** @var list<int> $questions */
                $questions = $quiz->questions()->pluck('id')->all();

                if ($questions === []) {
                    throw ValidationException::withMessages(['quiz' => __('quizzes.no_questions')]);
                }

                return QuizAttempt::query()->create([
                    'user_id' => $user->id,
                    'quiz_id' => $quiz->id,
                    'attempt_number' => $used + 1,
                    'questions' => $quiz->shuffle_questions ? (new Randomizer)->shuffleArray($questions) : $questions,
                    'started_at' => now(),
                    'expires_at' => $quiz->time_limit_seconds === null ? null : now()->addSeconds($quiz->time_limit_seconds),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // A second request raced this one: resume the attempt it opened.
            return QuizAttempt::query()->whereBelongsTo($user)->whereBelongsTo($quiz)->whereNull('submitted_at')->firstOrFail();
        }
    }
}
