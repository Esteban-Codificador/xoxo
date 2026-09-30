<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Videos\VideoDuration;
use App\Domain\Curriculum\Actions\SyncLessonRelations;
use App\Domain\Curriculum\Graph\CycleDetected;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncLessonRelationsRequest;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\Pivots\LessonDependency;
use App\Models\Skill;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Skills, prerequisites, resources and videos of a lesson. They are not versioned
 * (TD-6): a saved change is live, filtered to what is published.
 */
class LessonRelationsController extends Controller
{
    public function edit(Request $request, Lesson $lesson): Response
    {
        Gate::authorize('edit', $lesson);

        $lesson->load(['module.track', 'skills', 'prerequisites', 'resources', 'videos']);

        $candidates = Lesson::query()
            ->inRoadmapOf($lesson)
            ->whereKeyNot($lesson->id)
            ->select('lessons.*')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->join('tracks', 'tracks.id', '=', 'modules.track_id')
            ->orderBy('tracks.position')->orderBy('modules.position')->orderBy('lessons.position')
            ->with('module:id,title')
            ->get();

        return Inertia::render('admin/lessons/relations', [
            'lesson' => [
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'track' => $lesson->module->track->title,
                'module' => $lesson->module->title,
            ],
            'skills' => $lesson->skills->map(fn (Skill $skill) => [
                'id' => $skill->id,
                'weight' => (int) $skill->getRelationValue('pivot')->getAttribute('weight'),
            ])->values()->all(),
            'skill_options' => Skill::query()->orderBy('name')->get()->map(fn (Skill $skill) => [
                'id' => $skill->id,
                'title' => $skill->name,
                'published' => $skill->status === ContentStatus::Published,
            ])->values()->all(),
            'prerequisites' => $lesson->prerequisites->map(fn (Lesson $prerequisite) => [
                'id' => $prerequisite->id,
                'kind' => LessonDependency::of($prerequisite)->kind->value,
                // The lesson graph has no minimum progress; the editor hides it.
                'min_progress' => 100,
            ])->values()->all(),
            'prerequisite_options' => $candidates->map(fn (Lesson $candidate) => [
                'id' => $candidate->id,
                'title' => "{$candidate->title} ({$candidate->module->title})",
                'published' => $candidate->published_version_id !== null && $candidate->status !== ContentStatus::Archived,
            ])->values()->all(),
            'resources' => $lesson->resources->pluck('id')->values()->all(),
            'resource_options' => ExternalResource::query()->orderBy('title')->get()->map(fn (ExternalResource $resource) => [
                'id' => $resource->id,
                'title' => $resource->title,
                'provider' => $resource->provider,
                'type' => $resource->type->value,
                'url' => $resource->url,
                'is_official' => $resource->is_official,
                'link_status' => $resource->link_status->value,
                'published' => $resource->status === ContentStatus::Published,
            ])->values()->all(),
            'videos' => $lesson->videos->pluck('id')->values()->all(),
            'video_options' => Video::query()->orderBy('title')->get()->map(fn (Video $video) => [
                'id' => $video->id,
                'title' => $video->title,
                'instructor' => $video->instructor,
                'duration' => VideoDuration::format($video->duration_seconds),
                'url' => $video->url(),
                'link_status' => $video->link_status->value,
                'published' => $video->status === ContentStatus::Published,
            ])->values()->all(),
            // Frozen for the author while in review (ADR-032).
            'can' => ['save' => $request->user()?->can('update', $lesson) ?? false],
        ]);
    }

    public function update(SyncLessonRelationsRequest $request, Lesson $lesson, SyncLessonRelations $action): RedirectResponse
    {
        try {
            $action->handle($lesson, $request->skills(), $request->prerequisites(), $request->resources(), $request->videos());
        } catch (CycleDetected $cycle) {
            $titles = Lesson::query()->whereKey($cycle->path)->pluck('title', 'id');

            return back()->withErrors([
                'prerequisites' => __('cms.dependency_cycle', [
                    'path' => collect($cycle->path)->map(fn (int|string $id) => $titles[$id] ?? $id)->implode(' → '),
                ]),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.relations_saved')]);

        return back();
    }
}
