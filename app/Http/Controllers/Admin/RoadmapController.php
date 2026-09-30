<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\UpdateRoadmap;
use App\Enums\ContentStatus;
use App\Enums\UnlockPolicy;
use App\Http\Controllers\Admin\Concerns\ListsStatusActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRoadmapRequest;
use App\Models\Roadmap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The roadmap itself: what learners read above the graph, its unlock
 * policy and its status. Its tracks are edited from the track list.
 * Roadmaps are imported, not created here (there is one).
 */
class RoadmapController extends Controller
{
    use ListsStatusActions;

    public function edit(Request $request, Roadmap $roadmap): Response
    {
        Gate::authorize('update', $roadmap);

        return Inertia::render('admin/roadmaps/edit', [
            'roadmap' => [
                'id' => $roadmap->id,
                'slug' => $roadmap->slug,
                'title' => $roadmap->title,
                'summary' => $roadmap->summary,
                'description' => $roadmap->description,
                'unlock_policy' => $roadmap->unlock_policy->value,
                'status' => $roadmap->status->value,
                'published_at' => $roadmap->published_at?->toIso8601String(),
                'tracks' => $roadmap->tracks()->count(),
                'published_tracks' => $roadmap->tracks()->where('status', ContentStatus::Published)->count(),
            ],
            'unlock_policies' => UnlockPolicy::values(),
            'status_actions' => $this->statusActions($request, $roadmap),
        ]);
    }

    public function update(UpdateRoadmapRequest $request, Roadmap $roadmap, UpdateRoadmap $action): RedirectResponse
    {
        /** @var array{title: string, slug: string, summary: string, description: array<string, mixed>|null, unlock_policy: string} $data */
        $data = $request->validated();
        $action->handle($roadmap, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.roadmap_saved')]);

        return back();
    }
}
