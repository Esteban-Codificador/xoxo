<?php

namespace App\Http\Controllers\Learn;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoadmapSummaryResource;
use App\Http\Resources\TrackSummaryResource;
use App\Models\Roadmap;
use App\Models\Track;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $roadmap = Roadmap::query()->published()->orderBy('id')->first();

        $tracks = $roadmap === null ? collect() : Track::query()
            ->published()
            ->whereBelongsTo($roadmap)
            ->orderBy('position')
            ->withCount('visibleLessons as lessons_count')
            ->get();

        return Inertia::render('dashboard', [
            'roadmap' => $roadmap === null ? null : RoadmapSummaryResource::make($roadmap)->resolve(),
            'tracks' => TrackSummaryResource::collection($tracks)->resolve(),
        ]);
    }
}
