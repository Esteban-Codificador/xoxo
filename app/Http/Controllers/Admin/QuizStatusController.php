<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\ChangeContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeQuizStatusRequest;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class QuizStatusController extends Controller
{
    public function __invoke(ChangeQuizStatusRequest $request, Quiz $quiz, ChangeContentStatus $action): RedirectResponse
    {
        $action->handle($quiz, $request->target());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('quizzes.status.'.$quiz->status->value)]);

        return back();
    }
}
