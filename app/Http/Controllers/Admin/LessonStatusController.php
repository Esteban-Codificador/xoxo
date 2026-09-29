<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\ChangeContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeLessonStatusRequest;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class LessonStatusController extends Controller
{
    public function __invoke(ChangeLessonStatusRequest $request, Lesson $lesson, ChangeContentStatus $action): RedirectResponse
    {
        $action->handle($lesson, $request->target());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.lesson_status.'.$lesson->status->value)]);

        return back();
    }
}
