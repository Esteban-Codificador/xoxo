<?php

namespace App\Http\Controllers\Learn;

use App\Domain\Learning\Recommendations\Recommendation;
use App\Domain\Learning\Recommendations\RecommendationEngine;
use App\Domain\Learning\State\RoadmapStateResolver;
use App\Enums\NodeState;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoadmapSummaryResource;
use App\Http\Resources\TrackSummaryResource;
use App\Models\Roadmap;
use App\Models\Track;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, RoadmapStateResolver $states, RecommendationEngine $recommendations): Response
    {
        $roadmap = Roadmap::query()->published()->orderBy('id')->first();

        if ($roadmap === null) {
            return Inertia::render('dashboard', ['roadmap' => null, 'tracks' => [], 'recommendations' => []]);
        }

        $tracks = Track::query()
            ->published()
            ->whereBelongsTo($roadmap)
            ->orderBy('position')
            ->withCount('visibleLessons as lessons_count')
            ->get();

        $state = $states->resolve($request->user(), $roadmap);

        return Inertia::render('dashboard', [
            'roadmap' => RoadmapSummaryResource::make($roadmap)->resolve(),
            'tracks' => $tracks->map(fn (Track $track) => [
                ...TrackSummaryResource::make($track)->resolve(),
                'progress' => $state->track($track)->toArray(),
            ])->values()->all(),
            // What to study next and why (architecture §8, rules 1–3).
            'recommendations' => array_map(
                fn (Recommendation $recommendation) => $recommendation->toArray(),
                $recommendations->recommend($state),
            ),
            'all_done' => $tracks->isNotEmpty() && $tracks->every(
                fn (Track $track) => $state->track($track)->total === 0 || $state->track($track)->state === NodeState::Completed,
            ),
        ]);
    }
}
