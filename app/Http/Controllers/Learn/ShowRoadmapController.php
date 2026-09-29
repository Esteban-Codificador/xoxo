<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Learning\State\RoadmapStateResolver;
use App\Http\Controllers\Controller;
use App\Http\Resources\TrackSummaryResource;
use App\Models\Pivots\TrackDependency;
use App\Models\Roadmap;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The visual roadmap: tracks as nodes, their dependencies as edges, and
 * everything the side panel needs. States come from the resolver; the
 * client only lays the graph out.
 */
class ShowRoadmapController extends Controller
{
    public function __invoke(Request $request, Roadmap $roadmap, RoadmapStateResolver $states): Response
    {
        Gate::authorize('view', $roadmap);

        $tracks = Track::query()
            ->published()
            ->whereBelongsTo($roadmap)
            ->orderBy('position')
            ->orderBy('id')
            ->withCount('visibleLessons as lessons_count')
            ->get();

        $state = $states->resolve($request->user(), $roadmap);
        $slugs = $tracks->pluck('slug', 'id');

        $edges = TrackDependency::query()
            ->whereIn('track_id', $tracks->modelKeys())
            ->whereIn('prerequisite_track_id', $tracks->modelKeys())
            ->get()
            ->map(fn (TrackDependency $edge) => [
                'from' => $slugs[$edge->prerequisite_track_id],
                'to' => $slugs[$edge->track_id],
                'kind' => $edge->kind->value,
                'min_progress' => $edge->min_progress,
            ])
            ->values()
            ->all();

        return Inertia::render('roadmap/show', [
            'roadmap' => ['slug' => $roadmap->slug, 'title' => $roadmap->title, 'summary' => $roadmap->summary],
            'policy' => $state->policy->value,
            'tracks' => $tracks->map(function (Track $track) use ($state) {
                $lessons = $state->lessonsOf($track);
                $next = collect($lessons)->first(fn (array $lesson) => ! in_array($lesson['state']->value, ['COMPLETED', 'MASTERED'], true));

                return [
                    ...TrackSummaryResource::make($track)->resolve(),
                    'progress' => $state->track($track)->toArray(),
                    'lessons' => array_map(fn (array $lesson) => [...$lesson, 'state' => $lesson['state']->value], $lessons),
                    'continue' => $next === null ? null : ['slug' => $next['slug'], 'title' => $next['title']],
                ];
            })->values()->all(),
            'edges' => $edges,
        ]);
    }
}
