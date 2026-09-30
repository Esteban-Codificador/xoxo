<?php

use App\Domain\Assessment\Actions\StartQuizAttempt;
use App\Domain\Assessment\Actions\SubmitQuizAttempt;
use App\Domain\Assessment\QuestionTypes\OptionId;
use App\Domain\Learning\Actions\CompleteLesson;
use App\Enums\ActivityType;
use App\Enums\ProgressStatus;
use App\Models\LearningActivity;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/*
| Attempts (§35, ADR-036): opened and graded on the server, with the time
| and the attempts it allows, and the evidence a lesson needs for MASTERED.
*/

beforeEach(function () {
    $this->lesson = publishedLesson();
    $this->quiz = Quiz::factory()->for($this->lesson)->create(['pass_threshold' => 70]);
    // Ten one-point questions: the score is ten times the right answers.
    $this->questions = QuizQuestion::factory()->for($this->quiz)->count(10)->sequence(fn ($sequence) => ['position' => $sequence->index])->create();
    $this->user = User::factory()->create();
});

function startAttempt(User $user, Quiz $quiz): QuizAttempt
{
    return app(StartQuizAttempt::class)->handle($user, $quiz);
}

/** Answers the first $right questions correctly and the rest wrong. */
function quizAnswers(int $right, iterable $questions): array
{
    $answers = [];
    foreach ($questions as $index => $question) {
        $answers[$question->id] = ['choice' => OptionId::of($index < $right ? 'Correcta' : 'Incorrecta')];
    }

    return $answers;
}

function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt
{
    return app(SubmitQuizAttempt::class)->handle($attempt, $answers);
}

it('opens an attempt with its questions and resumes it instead of opening another', function () {
    $attempt = startAttempt($this->user, $this->quiz);

    expect($attempt->attempt_number)->toBe(1)
        ->and($attempt->questions)->toBe($this->questions->pluck('id')->all())
        ->and($attempt->expires_at)->toBeNull()
        ->and(startAttempt($this->user, $this->quiz)->id)->toBe($attempt->id)
        ->and(QuizAttempt::query()->count())->toBe(1);
});

it('shuffles the questions once, when the attempt starts', function () {
    $this->quiz->update(['shuffle_questions' => true]);
    $attempt = startAttempt($this->user, $this->quiz);

    expect(collect($attempt->questions)->sort()->values()->all())->toBe($this->questions->pluck('id')->all())
        ->and(startAttempt($this->user, $this->quiz)->questions)->toBe($attempt->questions);
});

it('grades on the server, stores each answer and records the result', function () {
    $attempt = submitAttempt(startAttempt($this->user, $this->quiz), quizAnswers(7, $this->questions));

    expect($attempt->only(['score', 'points_earned', 'points_total', 'passed']))->toBe(['score' => 70, 'points_earned' => 7, 'points_total' => 10, 'passed' => true])
        ->and($attempt->submitted_at)->not->toBeNull()
        ->and(QuizAttemptAnswer::query()->where('is_correct', false)->count())->toBe(3)
        ->and(LearningActivity::query()->where('type', ActivityType::QuizPassed)->value('metadata'))->toEqual(['attempt' => 1, 'score' => 70]);
});

it('counts a missing or malformed answer as wrong', function () {
    $answers = quizAnswers(10, $this->questions);
    $answers[$this->questions[0]->id] = ['choice' => 'inventada'];
    unset($answers[$this->questions[1]->id]);

    $attempt = submitAttempt(startAttempt($this->user, $this->quiz), $answers);

    expect($attempt->score)->toBe(80)
        ->and(QuizAttemptAnswer::query()->where('quiz_question_id', $this->questions[1]->id)->value('answer'))->toBeNull();
});

it('fails below the threshold and weighs questions by their points', function () {
    $this->questions[0]->update(['points' => 5]);

    // 1 right of 14 points: 5 → 35 %.
    $attempt = submitAttempt(startAttempt($this->user, $this->quiz), quizAnswers(1, $this->questions));

    expect($attempt->score)->toBe(35)
        ->and($attempt->passed)->toBeFalse()
        ->and(LearningActivity::query()->where('type', ActivityType::QuizFailed)->exists())->toBeTrue();
});

it('ignores a second submission of the same attempt', function () {
    $attempt = startAttempt($this->user, $this->quiz);
    submitAttempt($attempt, quizAnswers(3, $this->questions));

    $again = submitAttempt($attempt, quizAnswers(10, $this->questions));

    expect($again->score)->toBe(30)
        ->and(QuizAttemptAnswer::query()->count())->toBe(10);
});

it('grades the questions the attempt showed, even if the quiz changes meanwhile', function () {
    $attempt = startAttempt($this->user, $this->quiz);
    QuizQuestion::factory()->for($this->quiz)->create(['position' => 99]);
    $this->questions[9]->delete();

    $graded = submitAttempt($attempt, quizAnswers(9, $this->questions->take(9)));

    expect($graded->points_total)->toBe(9)->and($graded->score)->toBe(100);
});

it('keeps the time on the server and fails an attempt whose answers arrive too late', function () {
    $this->quiz->update(['time_limit_seconds' => 300]);
    $attempt = startAttempt($this->user, $this->quiz);

    expect($attempt->expires_at->equalTo($attempt->started_at->addSeconds(300)))->toBeTrue();

    // Within the grace period the answers still count.
    $this->travel(300 + QuizAttempt::GRACE_SECONDS - 5)->seconds();
    expect(submitAttempt($attempt, quizAnswers(10, $this->questions))->score)->toBe(100);

    $late = startAttempt($this->user, $this->quiz);
    $this->travel(301 + QuizAttempt::GRACE_SECONDS)->seconds();
    $graded = submitAttempt($late, quizAnswers(10, $this->questions));

    expect($graded->only(['score', 'passed', 'timed_out']))->toBe(['score' => 0, 'passed' => false, 'timed_out' => true])
        ->and($graded->submitted_at->equalTo($late->expires_at))->toBeTrue()
        ->and($graded->answers()->count())->toBe(0);
});

it('closes an overdue attempt when the learner starts again', function () {
    $this->quiz->update(['time_limit_seconds' => 60]);
    $first = startAttempt($this->user, $this->quiz);
    $this->travel(10)->minutes();

    $second = startAttempt($this->user, $this->quiz);

    expect($second->id)->not->toBe($first->id)
        ->and($second->attempt_number)->toBe(2)
        ->and($first->refresh()->timed_out)->toBeTrue();
});

it('stops at the maximum number of attempts', function () {
    $this->quiz->update(['max_attempts' => 1]);
    submitAttempt(startAttempt($this->user, $this->quiz), quizAnswers(2, $this->questions));

    startAttempt($this->user, $this->quiz);
})->throws(ValidationException::class);

it('does not open an attempt of a quiz without questions', function () {
    $empty = Quiz::factory()->create();

    startAttempt($this->user, $empty);
})->throws(ValidationException::class);

it('masters a completed lesson when the quiz is passed at the mastery threshold', function () {
    app(CompleteLesson::class)->handle($this->user, $this->lesson);

    submitAttempt(startAttempt($this->user, $this->quiz), quizAnswers(8, $this->questions));
    expect(LessonProgress::query()->value('status'))->toBe(ProgressStatus::Completed);

    submitAttempt(startAttempt($this->user, $this->quiz), quizAnswers(9, $this->questions));
    $progress = LessonProgress::query()->first();

    expect($progress->status)->toBe(ProgressStatus::Mastered)
        ->and($progress->mastered_at)->not->toBeNull()
        ->and(LearningActivity::query()->where('type', ActivityType::LessonMastered)->count())->toBe(1);
});

it('masters the lesson on completion when the quiz was already passed', function () {
    submitAttempt(startAttempt($this->user, $this->quiz), quizAnswers(10, $this->questions));
    expect(LessonProgress::query()->exists())->toBeFalse();

    app(CompleteLesson::class)->handle($this->user, $this->lesson);

    expect(LessonProgress::query()->value('status'))->toBe(ProgressStatus::Mastered);
});

it('asks for the pass threshold when it is above the mastery threshold', function () {
    $this->quiz->update(['pass_threshold' => 100]);
    app(CompleteLesson::class)->handle($this->user, $this->lesson);

    submitAttempt(startAttempt($this->user, $this->quiz), quizAnswers(9, $this->questions));

    expect(LessonProgress::query()->value('status'))->toBe(ProgressStatus::Completed);
});

it('does not count an unpublished quiz as evidence', function () {
    submitAttempt(startAttempt($this->user, $this->quiz), quizAnswers(10, $this->questions));
    $this->quiz->update(['status' => 'DRAFT']);

    app(CompleteLesson::class)->handle($this->user, $this->lesson);

    expect(LessonProgress::query()->value('status'))->toBe(ProgressStatus::Completed);
});
