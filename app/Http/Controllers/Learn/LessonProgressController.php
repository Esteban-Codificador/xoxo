<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Learning\Actions\CompleteLesson;
use App\Domain\Learning\Actions\StartLesson;
use App\Domain\Learning\Actions\UncompleteLesson;
use App\Http\Controllers\Controller;
use App\Http\Requests\LessonProgressRequest;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;

/**
 * The Lesson parameter is not optional: Laravel only binds {lesson} to a
 * model when the controller method type-hints it.
 */
class LessonProgressController extends Controller
{
    public function start(LessonProgressRequest $request, Lesson $lesson, StartLesson $action): RedirectResponse
    {
        $action->handle($request->user(), $lesson);

        return back();
    }

    public function complete(LessonProgressRequest $request, Lesson $lesson, CompleteLesson $action): RedirectResponse
    {
        $action->handle($request->user(), $lesson);

        return back();
    }

    public function uncomplete(LessonProgressRequest $request, Lesson $lesson, UncompleteLesson $action): RedirectResponse
    {
        $action->handle($request->user(), $lesson);

        return back();
    }
}
