<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Publishing\LessonChanges;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonVersion;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only history of a lesson: a published version against the one
 * before it, and the working copy against the published version. Part of
 * editing, so it takes the same permission.
 */
class LessonVersionController extends Controller
{
    public function show(Lesson $lesson, LessonVersion $version, LessonChanges $changes): Response
    {
        Gate::authorize('update', $lesson);

        $version->load('publisher:id,name');
        $previous = $lesson->versions()->where('version', '<', $version->version)->first();

        return Inertia::render('admin/lessons/version', [
            'lesson' => $this->lesson($lesson),
            'version' => [
                'version' => $version->version,
                'published_at' => $version->published_at->toIso8601String(),
                'published_by' => $version->publisher?->name,
                'change_note' => $version->change_note,
                'current' => $version->id === $lesson->published_version_id,
            ],
            'previous' => $previous?->version,
            'changes' => $previous === null ? null : $changes->between($previous->versionedFields(), $version->versionedFields()),
            'content' => [
                'title' => $version->title,
                'summary' => $version->summary,
                'why_it_matters' => $version->why_it_matters,
                'learning_objectives' => $version->learning_objectives,
                'content_type' => $version->content_type->value,
                'difficulty' => $version->difficulty->value,
                'estimated_minutes' => $version->estimated_minutes,
                'body' => $version->body,
            ],
        ]);
    }

    public function changes(Lesson $lesson, LessonChanges $changes): Response
    {
        Gate::authorize('update', $lesson);

        $published = $lesson->publishedVersion;

        return Inertia::render('admin/lessons/changes', [
            'lesson' => $this->lesson($lesson),
            'published' => $published?->version,
            'changes' => $published === null ? null : $changes->between($published->versionedFields(), $lesson->versionedFields()),
        ]);
    }

    /**
     * @return array{slug: string, title: string}
     */
    private function lesson(Lesson $lesson): array
    {
        return ['slug' => $lesson->slug, 'title' => $lesson->title];
    }
}
