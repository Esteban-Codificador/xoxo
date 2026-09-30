<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Assessment\Actions\StartQuizAttempt;
use App\Domain\Assessment\Actions\SubmitQuizAttempt;
use App\Domain\Assessment\AttemptSheet;
use App\Domain\Assessment\QuizRecord;
use App\Http\Controllers\Controller;
use App\Http\Requests\StartQuizAttemptRequest;
use App\Http\Requests\SubmitQuizAttemptRequest;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One URL per attempt: the questions while it is open, the review once it
 * is graded. The Lesson parameter is not optional: Laravel only binds
 * {lesson} when the method type-hints it.
 */
class QuizAttemptController extends Controller
{
    public function store(StartQuizAttemptRequest $request, Lesson $lesson, StartQuizAttempt $action): RedirectResponse
    {
        $attempt = $action->handle($request->user(), $request->quiz());

        return to_route('quiz-attempts.show', $attempt);
    }

    public function show(Request $request, QuizAttempt $attempt, AttemptSheet $sheet): Response
    {
        Gate::authorize('view', $attempt);

        $user = $request->user();
        $quiz = $attempt->quiz()->with('lesson.publishedVersion')->firstOrFail();
        $record = QuizRecord::of($user, $quiz);
        $attempt = $record->attempts->firstWhere('id', $attempt->id) ?? $attempt->refresh();
        $lesson = $quiz->lesson;
        $graded = ! $attempt->isOpen();
        $sheetData = $sheet->for($attempt, $graded && $record->reveals($attempt));

        return Inertia::render('quizzes/attempt', [
            'lesson' => ['slug' => $lesson->slug, 'title' => $lesson->publishedVersion->title ?? $lesson->title],
            'quiz' => ShowQuizController::quiz($quiz),
            'attempt' => [
                'id' => $attempt->id,
                'number' => $attempt->attempt_number,
                'open' => ! $graded,
                'seconds_left' => $graded ? null : $attempt->secondsLeft(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'score' => $attempt->score,
                'points_earned' => $attempt->points_earned,
                'points_total' => $attempt->points_total,
                'passed' => $attempt->passed,
                'timed_out' => $attempt->timed_out,
                'masters' => $attempt->passed === true && $quiz->masters((int) $attempt->score),
            ],
            'questions' => $sheetData['questions'],
            'media' => $sheetData['media'],
            'revealed' => $graded && $record->reveals($attempt),
            'attempts_left' => $record->attemptsLeft(),
            'can_retry' => $graded && $record->open() === null && $record->attemptsLeft() !== 0 && Gate::allows('take', $quiz),
            'lesson_status' => LessonProgress::query()->whereBelongsTo($user)->whereBelongsTo($lesson)->value('status'),
        ]);
    }

    public function update(SubmitQuizAttemptRequest $request, QuizAttempt $attempt, SubmitQuizAttempt $action): RedirectResponse
    {
        $action->handle($attempt, $request->answers());

        return to_route('quiz-attempts.show', $attempt);
    }
}
