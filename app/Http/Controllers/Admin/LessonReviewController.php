<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\ResolveLessonReview;
use App\Domain\Curriculum\Actions\SubmitLessonForReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReturnLessonReviewRequest;
use App\Http\Requests\Admin\SubmitLessonReviewRequest;
use App\Http\Requests\Admin\WithdrawLessonReviewRequest;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Review flow of a lesson (ADR-032): submit, return with comments,
 * withdraw. Publishing from review goes through PublishLessonController.
 */
class LessonReviewController extends Controller
{
    public function store(SubmitLessonReviewRequest $request, Lesson $lesson, SubmitLessonForReview $action): RedirectResponse
    {
        $action->handle($lesson, $this->user($request->user()), $request->validated('note'));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.review.submitted')]);

        return back();
    }

    public function sendBack(ReturnLessonReviewRequest $request, Lesson $lesson, ResolveLessonReview $action): RedirectResponse
    {
        $action->returnWithComment($lesson, $this->user($request->user()), (string) $request->validated('comment'));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.review.returned')]);

        return back();
    }

    public function destroy(WithdrawLessonReviewRequest $request, Lesson $lesson, ResolveLessonReview $action): RedirectResponse
    {
        $action->withdraw($lesson, $this->user($request->user()));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.review.withdrawn')]);

        return back();
    }

    private function user(mixed $user): User
    {
        return $user instanceof User ? $user : abort(403);
    }
}
