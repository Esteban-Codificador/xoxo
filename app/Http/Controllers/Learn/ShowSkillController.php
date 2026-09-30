<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Learning\State\RoadmapStateResolver;
use App\Domain\Learning\State\SkillProgressCalculator;
use App\Http\Controllers\Controller;
use App\Http\Resources\ResourceLinkResource;
use App\Models\Pivots\SkillDependency;
use App\Models\Roadmap;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One skill: what it is, how far the learner is, the lessons that develop
 * it, what to develop first and what it leads to (master spec §38).
 */
class ShowSkillController extends Controller
{
    public function __invoke(Request $request, Skill $skill, RoadmapStateResolver $states, SkillProgressCalculator $calculator): Response
    {
        Gate::authorize('view', $skill);

        $roadmap = Roadmap::query()->published()->orderBy('id')->firstOrFail();
        $roadmapState = $states->resolve($request->user(), $roadmap);
        $skills = $calculator->calculate($roadmapState);

        $prerequisites = $skill->prerequisites()->published()->orderBy('name')->get();
        $enables = $skill->dependents()->published()->orderBy('name')->get();

        return Inertia::render('skills/show', [
            'roadmap' => ['slug' => $roadmap->slug, 'title' => $roadmap->title],
            'skill' => [
                'slug' => $skill->slug,
                'name' => $skill->name,
                'description' => $skill->description,
                'difficulty' => $skill->difficulty->value,
            ],
            'progress' => $skills->state($skill->id)->toArray(),
            'prerequisites' => $prerequisites->map(fn (Skill $prerequisite) => [
                'slug' => $prerequisite->slug,
                'name' => $prerequisite->name,
                'kind' => SkillDependency::of($prerequisite)->kind->value,
                'min_progress' => SkillDependency::of($prerequisite)->min_progress,
                'state' => $skills->state($prerequisite->id)->state->value,
                'progress' => $skills->state($prerequisite->id)->progress,
            ])->values()->all(),
            'enables' => $enables->map(fn (Skill $dependent) => [
                'slug' => $dependent->slug,
                'name' => $dependent->name,
                'state' => $skills->state($dependent->id)->state->value,
            ])->values()->all(),
            // In study order, with where each lesson sits.
            'lessons' => array_map(fn (int $lesson) => [
                'slug' => $roadmapState->lessonInfo($lesson)['slug'],
                'title' => $roadmapState->lessonInfo($lesson)['title'],
                'track' => $roadmapState->trackInfo($roadmapState->lessonInfo($lesson)['track_id'])['title'],
                'state' => $roadmapState->lesson($lesson)->state->value,
            ], $skills->lessonIdsOf($skill->id)),
            'resources' => ResourceLinkResource::collection($skill->resources()->published()->get())->resolve(),
        ]);
    }
}
