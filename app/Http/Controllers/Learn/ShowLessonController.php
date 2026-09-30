<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Content\Media\MediaSources;
use App\Domain\Curriculum\Queries\TrackOutline;
use App\Domain\Learning\State\Blocker;
use App\Domain\Learning\State\RoadmapStateResolver;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonLinkResource;
use App\Http\Resources\LessonPageResource;
use App\Http\Resources\ResourceLinkResource;
use App\Http\Resources\VideoLinkResource;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Pivots\LessonDependency;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShowLessonController extends Controller
{
    public function __invoke(Request $request, Lesson $lesson, RoadmapStateResolver $states, MediaSources $media): Response
    {
        Gate::authorize('view', $lesson);

        $lesson->load(['publishedVersion', 'module.track.roadmap']);
        $module = $lesson->module;
        $track = $module->track;
        $neighbours = TrackOutline::for($track)->neighboursOf($lesson);
        $user = $request->user();
        $state = $states->resolve($user, $track->roadmap);
        $lessonState = $state->lesson($lesson);
        $row = LessonProgress::query()->whereBelongsTo($user)->whereBelongsTo($lesson)->first();

        $prerequisites = $lesson->prerequisites()
            ->visibleToLearners()
            ->with('publishedVersion')
            ->get();

        $skills = $lesson->skills()
            ->published()
            ->orderByPivot('weight', 'desc')
            ->orderBy('name')
            ->get();

        return Inertia::render('lessons/show', [
            'roadmap' => ['slug' => $track->roadmap->slug, 'title' => $track->roadmap->title],
            'track' => ['slug' => $track->slug, 'title' => $track->title],
            'module' => ['slug' => $module->slug, 'title' => $module->title],
            'lesson' => LessonPageResource::make($lesson)->resolve(),
            'media' => $media->for($lesson->publishedVersion?->body),
            'prerequisites' => $prerequisites->map(fn (Lesson $prerequisite) => [
                ...LessonLinkResource::make($prerequisite)->resolve(),
                'kind' => LessonDependency::of($prerequisite)->kind->value,
                'state' => $state->lesson($prerequisite)->state->value,
            ])->values()->all(),
            // Ordered by how much the lesson develops each skill.
            'skills' => $skills->map(fn (Skill $skill) => [
                'slug' => $skill->slug,
                'name' => $skill->name,
            ])->values()->all(),
            'resources' => ResourceLinkResource::collection($lesson->resources()->published()->get())->resolve(),
            // Only what YouTube confirmed on the last check (ADR-035).
            'videos' => VideoLinkResource::collection($lesson->videos()->visibleToLearners()->get())->resolve(),
            'previous' => $neighbours['previous'] === null ? null : LessonLinkResource::make($neighbours['previous'])->resolve(),
            'next' => $neighbours['next'] === null ? null : LessonLinkResource::make($neighbours['next'])->resolve(),
            'progress' => [
                'state' => $lessonState->state->value,
                'blockers' => array_map(fn (Blocker $blocker) => $blocker->toArray(), $lessonState->blockers),
                'policy' => $state->policy->value,
                'can_progress' => $state->canProgress($lesson),
                'starts_on_open' => $state->startsOnOpen($lesson),
                'completed_at' => $row?->completed_at?->toIso8601String(),
            ],
        ]);
    }
}
