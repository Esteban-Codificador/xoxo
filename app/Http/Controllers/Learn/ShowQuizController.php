<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Assessment\QuizRecord;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** The quiz of a lesson: its rules, the learner's attempts and the way in. */
class ShowQuizController extends Controller
{
    public function __invoke(Request $request, Lesson $lesson): Response
    {
        $quiz = $lesson->quiz ?? abort(404);
        Gate::authorize('view', $quiz);

        $user = $request->user();
        $lesson->load(['publishedVersion', 'module.track.roadmap']);
        $track = $lesson->module->track;
        $record = QuizRecord::of($user, $quiz);
        $open = $record->open();
        $take = Gate::inspect('take', $quiz);

        return Inertia::render('quizzes/show', [
            'roadmap' => ['slug' => $track->roadmap->slug],
            'track' => ['slug' => $track->slug, 'title' => $track->title],
            'lesson' => ['slug' => $lesson->slug, 'title' => $lesson->publishedVersion->title ?? $lesson->title],
            'quiz' => self::quiz($quiz),
            'attempts' => $record->submitted()->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'number' => $attempt->attempt_number,
                'score' => $attempt->score,
                'passed' => (bool) $attempt->passed,
                'timed_out' => $attempt->timed_out,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            ])->all(),
            'open_attempt' => $open === null ? null : ['id' => $open->id, 'seconds_left' => $open->secondsLeft()],
            'best_score' => $record->bestScore(),
            'passed' => $record->passed(),
            'attempts_left' => $record->attemptsLeft(),
            'lesson_status' => LessonProgress::query()->whereBelongsTo($user)->whereBelongsTo($lesson)->value('status'),
            // Why the learner cannot start, when the roadmap is STRICT and the lesson LOCKED.
            'blocked' => $take->denied() ? $take->message() : null,
        ]);
    }

    /**
     * @return array{title: string, description: string|null, questions: int, pass_threshold: int, mastery_threshold: int, time_limit_seconds: int|null, max_attempts: int|null}
     */
    public static function quiz(Quiz $quiz): array
    {
        return [
            'title' => $quiz->title,
            'description' => $quiz->description,
            'questions' => $quiz->questions()->count(),
            'pass_threshold' => $quiz->pass_threshold,
            'mastery_threshold' => max(Quiz::MASTERY_THRESHOLD, $quiz->pass_threshold),
            'time_limit_seconds' => $quiz->time_limit_seconds,
            'max_attempts' => $quiz->max_attempts,
        ];
    }
}
