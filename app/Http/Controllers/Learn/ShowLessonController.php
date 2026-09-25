<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Curriculum\Queries\TrackOutline;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonLinkResource;
use App\Http\Resources\LessonPageResource;
use App\Http\Resources\ResourceLinkResource;
use App\Models\Lesson;
use App\Models\Pivots\LessonDependency;
use App\Models\Skill;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShowLessonController extends Controller
{
    public function __invoke(Lesson $lesson): Response
    {
        Gate::authorize('view', $lesson);

        $lesson->load(['publishedVersion', 'module.track.roadmap']);
        $module = $lesson->module;
        $track = $module->track;
        $neighbours = TrackOutline::for($track)->neighboursOf($lesson);

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
            'prerequisites' => $prerequisites->map(fn (Lesson $prerequisite) => [
                ...LessonLinkResource::make($prerequisite)->resolve(),
                'kind' => LessonDependency::of($prerequisite)->kind->value,
            ])->values()->all(),
            // Ordered by how much the lesson develops each skill.
            'skills' => $skills->map(fn (Skill $skill) => [
                'slug' => $skill->slug,
                'name' => $skill->name,
            ])->values()->all(),
            'resources' => ResourceLinkResource::collection($lesson->resources()->published()->get())->resolve(),
            'previous' => $neighbours['previous'] === null ? null : LessonLinkResource::make($neighbours['previous'])->resolve(),
            'next' => $neighbours['next'] === null ? null : LessonLinkResource::make($neighbours['next'])->resolve(),
        ]);
    }
}
