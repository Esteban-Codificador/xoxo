<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Curriculum\Queries\TrackOutline;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonListItemResource;
use App\Http\Resources\TrackDetailResource;
use App\Models\Module;
use App\Models\Pivots\TrackDependency;
use App\Models\Roadmap;
use App\Models\Track;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShowTrackController extends Controller
{
    public function __invoke(Roadmap $roadmap, Track $track): Response
    {
        $track->setRelation('roadmap', $roadmap);
        Gate::authorize('view', $track);

        $outline = TrackOutline::for($track);

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
            'prerequisites' => $prerequisites->map(fn (Track $prerequisite) => [
                'slug' => $prerequisite->slug,
                'title' => $prerequisite->title,
                'kind' => TrackDependency::of($prerequisite)->kind->value,
            ])->values()->all(),
            'modules' => $outline->modules->map(fn (Module $module) => [
                'slug' => $module->slug,
                'title' => $module->title,
                'summary' => $module->summary,
                'lessons' => LessonListItemResource::collection($module->visibleLessons)->resolve(),
            ])->values()->all(),
        ]);
    }
}
