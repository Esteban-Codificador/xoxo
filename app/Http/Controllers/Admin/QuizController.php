<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** Every quiz with its lesson; each one is edited in its lesson's quiz tab. */
class QuizController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Quiz::class);

        $quizzes = Quiz::query()
            ->with('lesson.module.track')
            ->withCount(['questions', 'attempts' => fn ($query) => $query->whereNotNull('submitted_at')])
            ->withAvg(['attempts' => fn ($query) => $query->whereNotNull('submitted_at')], 'score')
            ->orderBy('title')
            ->get();

        return Inertia::render('admin/quizzes/index', [
            'quizzes' => $quizzes->map(fn (Quiz $quiz) => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'lesson' => ['slug' => $quiz->lesson->slug, 'title' => $quiz->lesson->title],
                'track' => $quiz->lesson->module->track->title,
                'status' => $quiz->status->value,
                'questions' => (int) $quiz->getAttribute('questions_count'),
                'attempts' => (int) $quiz->getAttribute('attempts_count'),
                'average_score' => $quiz->getAttribute('attempts_avg_score') === null ? null : (int) round((float) $quiz->getAttribute('attempts_avg_score')),
            ])->values()->all(),
        ]);
    }
}
