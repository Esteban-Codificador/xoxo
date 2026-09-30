<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Content\Media\MediaSources;
use App\Domain\Curriculum\Queries\TrackOutline;
use App\Domain\Learning\State\RoadmapStateResolver;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonLinkResource;
use App\Http\Resources\LessonListItemResource;
use App\Http\Resources\TrackDetailResource;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Pivots\TrackDependency;
use App\Models\Roadmap;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShowTrackController extends Controller
{
    public function __invoke(Request $request, Roadmap $roadmap, Track $track, RoadmapStateResolver $states, MediaSources $media): Response
    {
        $track->setRelation('roadmap', $roadmap);
        Gate::authorize('view', $track);

        $outline = TrackOutline::for($track);
        $state = $states->resolve($request->user(), $roadmap);
        $trackState = $state->track($track);
        $continue = $outline->lessons()->first(fn (Lesson $lesson) => ! $state->lesson($lesson)->isDone());

        $prerequisites = $track->prerequisites()
            ->published()
            ->reorder()
            ->orderBy('position')
            ->get();

        return Inertia::render('tracks/show', [
            'roadmap' => ['slug' => $roadmap->slug, 'title' => $roadmap->title],
            'track' => [
                ...TrackDetailResource::make($track)->resolve(),
                'lessons_count' => $outline->lessons()->count(),
                'total_minutes' => $outline->totalMinutes(),
            ],
            'media' => $media->for($track->description),
            'progress' => $trackState->toArray(),
            'policy' => $state->policy->value,
            // First lesson not done yet, in study order: "Empezar" or "Continuar".
            'continue' => $continue === null ? null : LessonLinkResource::make($continue)->resolve(),
            'prerequisites' => $prerequisites->map(fn (Track $prerequisite) => [
                'slug' => $prerequisite->slug,
                'title' => $prerequisite->title,
                'kind' => TrackDependency::of($prerequisite)->kind->value,
            ])->values()->all(),
            'modules' => $outline->modules->map(fn (Module $module) => [
                'slug' => $module->slug,
                'title' => $module->title,
                'summary' => $module->summary,
                'lessons' => $module->visibleLessons->map(fn (Lesson $lesson) => [
                    ...LessonListItemResource::make($lesson)->resolve(),
                    'state' => $state->lesson($lesson)->state->value,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }
}
