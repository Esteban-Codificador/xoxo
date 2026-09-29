<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonReview;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lessons waiting for an editor, oldest first.
 */
class ReviewQueueController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('reviewQueue', Lesson::class);

        $reviews = LessonReview::query()->open()
            ->with(['submitter:id,name', 'lesson.module.track', 'lesson.publishedVersion'])
            ->orderBy('submitted_at')
            ->get();

        return Inertia::render('admin/reviews/index', [
            'reviews' => $reviews->map(fn (LessonReview $review) => [
                'id' => $review->id,
                'lesson' => [
                    'slug' => $review->lesson->slug,
                    'title' => $review->lesson->title,
                    'track' => $review->lesson->module->track->title,
                    'module' => $review->lesson->module->title,
                    'published_version' => $review->lesson->publishedVersion?->version,
                ],
                'submitted_by' => $review->submitter?->name,
                'submitted_at' => $review->submitted_at->toIso8601String(),
                'note' => $review->note,
                // A reviewer may have fixed it since it was sent.
                'edited_since' => $review->content_hash !== $review->lesson->workingCopyHash(),
            ])->values()->all(),
        ]);
    }
}
