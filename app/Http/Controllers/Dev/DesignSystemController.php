<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Local-only gallery (routes/web.php registers it only in the local
 * environment): the design tokens and components next to a real published
 * lesson, to review them in light, dark and mobile.
 */
class DesignSystemController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $lessons = Lesson::query()
            ->visibleToLearners()
            ->with('publishedVersion')
            ->orderBy('id')
            ->get();

        $selected = $lessons->firstWhere('slug', $request->query('lesson')) ?? $lessons->first();

        return Inertia::render('dev/design-system', [
            'lessons' => $lessons->map(fn (Lesson $lesson) => [
                'slug' => $lesson->slug,
                'title' => $lesson->title,
            ])->values()->all(),
            'lesson' => $selected?->publishedVersion === null ? null : [
                'slug' => $selected->slug,
                'title' => $selected->publishedVersion->title,
                'body' => $selected->publishedVersion->body,
            ],
        ]);
    }
}
