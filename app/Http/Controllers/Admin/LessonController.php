<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\UpdateLesson;
use App\Domain\Curriculum\Publishing\LessonDraft;
use App\Domain\Curriculum\Publishing\LessonReadiness;
use App\Domain\Curriculum\Publishing\ReadinessIssue;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Http\Controllers\Admin\Concerns\ListsStatusActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateLessonRequest;
use App\Models\Lesson;
use App\Models\LessonVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CMS for lessons. Editing changes the working copy only; learners read
 * the published version until someone publishes (ADR-006).
 */
class LessonController extends Controller
{
    use ListsStatusActions;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Lesson::class);

        $lessons = Lesson::query()
            ->select('lessons.*')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->join('tracks', 'tracks.id', '=', 'modules.track_id')
            ->with(['module.track', 'publishedVersion'])
            ->orderBy('tracks.position')->orderBy('modules.position')
            ->orderBy('lessons.position')->orderBy('lessons.id')
            ->get();

        return Inertia::render('admin/lessons/index', [
            'lessons' => $lessons->map(fn (Lesson $lesson) => [
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'track' => $lesson->module->track->title,
                'module' => $lesson->module->title,
                'status' => $lesson->status->value,
                'version' => $lesson->publishedVersion?->version,
                'has_unpublished_changes' => $lesson->published_version_id !== null && $lesson->hasUnpublishedChanges(),
                'updated_at' => $lesson->updated_at?->toIso8601String(),
                'can_edit' => $request->user()?->can('update', $lesson) ?? false,
            ])->values()->all(),
        ]);
    }

    public function edit(Request $request, Lesson $lesson, LessonReadiness $readiness): Response
    {
        Gate::authorize('update', $lesson);

        $lesson->load(['module.track.roadmap', 'publishedVersion']);
        $versions = $lesson->versions()->with('publisher:id,name')->orderByDesc('version')->get();
        $changed = $lesson->published_version_id === null || $lesson->hasUnpublishedChanges();

        return Inertia::render('admin/lessons/edit', [
            'lesson' => [
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'summary' => $lesson->summary,
                'why_it_matters' => $lesson->why_it_matters,
                'learning_objectives' => $lesson->learning_objectives,
                'content_type' => $lesson->content_type->value,
                'difficulty' => $lesson->difficulty->value,
                'estimated_minutes' => $lesson->estimated_minutes,
                'body' => $lesson->body,
                'status' => $lesson->status->value,
                'track' => $lesson->module->track->title,
                'module' => $lesson->module->title,
            ],
            // Computed on the saved working copy: the checklist a publish would run.
            'readiness' => array_map(
                fn (ReadinessIssue $issue) => ['code' => $issue->code, 'message' => $issue->message()],
                $readiness->check(LessonDraft::fromLesson($lesson)),
            ),
            'publication' => [
                'version' => $lesson->publishedVersion?->version,
                'published_at' => $lesson->publishedVersion?->published_at->toIso8601String(),
                'live' => $lesson->status === ContentStatus::Published,
                'has_unpublished_changes' => $changed,
                // Unchanged content re-activates the current version (PublishLesson);
                // null when it is already live and there is nothing to publish.
                'next_version' => match (true) {
                    $changed => (int) $versions->max('version') + 1,
                    $lesson->status !== ContentStatus::Published => $lesson->publishedVersion?->version,
                    default => null,
                },
                'visible_to_learners' => Lesson::query()->visibleToLearners()->whereKey($lesson->id)->exists(),
            ],
            'status_actions' => $this->statusActions($request, $lesson),
            'versions' => $versions->map(fn (LessonVersion $version) => [
                'version' => $version->version,
                'published_at' => $version->published_at->toIso8601String(),
                'published_by' => $version->publisher?->name,
                'change_note' => $version->change_note,
                'current' => $version->id === $lesson->published_version_id,
            ])->values()->all(),
            'content_types' => ContentType::lessonValues(),
            'can' => ['publish' => $request->user()?->can('publish', $lesson) ?? false],
        ]);
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson, UpdateLesson $action): RedirectResponse
    {
        /** @var array{title: string, slug: string, summary: string, why_it_matters: string, learning_objectives: list<string>, content_type: string, difficulty: string, estimated_minutes: int|string, body: array<string, mixed>} $data */
        $data = $request->validated();
        $previousSlug = $lesson->slug;
        $action->handle($lesson, $request->user(), $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.saved')]);

        // A new slug is a new URL: go to it instead of back to the old one.
        return $lesson->slug === $previousSlug
            ? back()
            : to_route('admin.lessons.edit', ['lesson' => $lesson->slug]);
    }
}
