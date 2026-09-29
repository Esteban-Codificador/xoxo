<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\SyncTrackDependencies;
use App\Domain\Curriculum\Graph\CycleDetected;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncTrackDependenciesRequest;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TrackDependencyController extends Controller
{
    public function __invoke(SyncTrackDependenciesRequest $request, Track $track, SyncTrackDependencies $action): RedirectResponse
    {
        try {
            $action->handle($track, $request->dependencies());
        } catch (CycleDetected $cycle) {
            $titles = Track::query()->whereKey($cycle->path)->pluck('title', 'id');

            return back()->withErrors([
                'dependencies' => __('cms.dependency_cycle', [
                    'path' => collect($cycle->path)->map(fn (int|string $id) => $titles[$id] ?? $id)->implode(' → '),
                ]),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.dependencies_saved')]);

        return back();
    }
}
