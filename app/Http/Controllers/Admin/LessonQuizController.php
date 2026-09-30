<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assessment\Actions\SaveQuiz;
use App\Domain\Content\Media\MediaSources;
use App\Http\Controllers\Admin\Concerns\ListsStatusActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveQuizRequest;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The quiz tab of a lesson (§35, ADR-036): settings and questions, saved
 * together. Not versioned: what is saved is what learners get, and a
 * published quiz changes for the next attempt, never for one in progress.
 */
class LessonQuizController extends Controller
{
    use ListsStatusActions;

    public function edit(Request $request, Lesson $lesson, MediaSources $media): Response
    {
        Gate::authorize('edit', $lesson);

        $lesson->load('module.track');
        $quiz = $lesson->quiz()->with('questions')->first();
        $questions = $quiz->questions ?? collect();

        return Inertia::render('admin/lessons/quiz', [
            'lesson' => [
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'track' => $lesson->module->track->title,
                'module' => $lesson->module->title,
            ],
            'quiz' => $quiz === null ? null : [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'pass_threshold' => $quiz->pass_threshold,
                'time_limit_minutes' => $quiz->time_limit_seconds === null ? null : intdiv($quiz->time_limit_seconds, 60),
                'max_attempts' => $quiz->max_attempts,
                'shuffle_questions' => $quiz->shuffle_questions,
                'status' => $quiz->status->value,
                'attempts' => QuizAttempt::query()->whereBelongsTo($quiz)->whereNotNull('submitted_at')->count(),
                'questions' => $questions->map(fn (QuizQuestion $question) => [
                    'id' => $question->id,
                    'type' => $question->type->value,
                    'prompt' => $question->prompt->toArray(),
                    'payload' => $question->payload,
                    'explanation' => $question->explanation->toArray(),
                    'difficulty' => $question->difficulty?->value,
                    'points' => $question->points,
                ])->values()->all(),
            ],
            'media' => $media->for(...$questions->flatMap(fn (QuizQuestion $question) => [$question->prompt, $question->explanation])->all()),
            'status_actions' => $quiz === null ? [] : $this->statusActions($request, $quiz),
            'can' => ['save' => Gate::allows('update', $lesson)],
        ]);
    }

    public function update(SaveQuizRequest $request, Lesson $lesson, SaveQuiz $action): RedirectResponse
    {
        $created = $lesson->quiz()->doesntExist();
        $action->handle($lesson, $request->settings(), $request->questions());

        Inertia::flash('toast', ['type' => 'success', 'message' => __($created ? 'quizzes.created' : 'quizzes.saved')]);

        return back();
    }
}
