<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\PublishLesson;
use App\Domain\Curriculum\Publishing\LessonNotReadyToPublish;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublishLessonRequest;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PublishLessonController extends Controller
{
    public function __invoke(PublishLessonRequest $request, Lesson $lesson, PublishLesson $publish): RedirectResponse
    {
        $previous = $lesson->published_version_id;

        try {
            $version = $publish->handle($lesson, $request->validated('change_note'));
        } catch (LessonNotReadyToPublish) {
            return back()->withErrors(['publish' => __('cms.not_ready')]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $version->id === $previous
                ? __('cms.unchanged', ['version' => $version->version])
                : __('cms.published', ['version' => $version->version]),
        ]);

        return back();
    }
}
