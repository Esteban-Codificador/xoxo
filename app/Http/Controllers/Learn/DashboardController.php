<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Learning\State\RoadmapStateResolver;
use App\Enums\NodeState;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoadmapSummaryResource;
use App\Http\Resources\TrackSummaryResource;
use App\Models\Lesson;
use App\Models\Roadmap;
use App\Models\Track;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, RoadmapStateResolver $states): Response
    {
        $roadmap = Roadmap::query()->published()->orderBy('id')->first();

        if ($roadmap === null) {
            return Inertia::render('dashboard', ['roadmap' => null, 'tracks' => [], 'continue' => null]);
        }

        $tracks = Track::query()
            ->published()
            ->whereBelongsTo($roadmap)
            ->orderBy('position')
            ->withCount('visibleLessons as lessons_count')
            ->get();

        $state = $states->resolve($request->user(), $roadmap);
        $nextId = $state->nextLessonId();
        $next = $nextId === null ? null : Lesson::query()->with(['publishedVersion', 'module.track'])->find($nextId);

        return Inertia::render('dashboard', [
            'roadmap' => RoadmapSummaryResource::make($roadmap)->resolve(),
            'tracks' => $tracks->map(fn (Track $track) => [
                ...TrackSummaryResource::make($track)->resolve(),
                'progress' => $state->track($track)->toArray(),
            ])->values()->all(),
            // The lesson to resume (last visited in progress) or to start with.
            'continue' => $next === null ? null : [
                'slug' => $next->slug,
                'title' => $next->publishedVersion->title,
                'track' => $next->module->track->title,
                'state' => $state->lesson($next)->state->value,
            ],
            'all_done' => $tracks->isNotEmpty() && $tracks->every(
                fn (Track $track) => $state->track($track)->total === 0 || $state->track($track)->state === NodeState::Completed,
            ),
        ]);
    }
}
