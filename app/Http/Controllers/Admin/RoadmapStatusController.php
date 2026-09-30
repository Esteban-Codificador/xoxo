<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\ChangeContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeRoadmapStatusRequest;
use App\Models\Roadmap;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RoadmapStatusController extends Controller
{
    public function __invoke(ChangeRoadmapStatusRequest $request, Roadmap $roadmap, ChangeContentStatus $action): RedirectResponse
    {
        $action->handle($roadmap, $request->target());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.roadmap_status.'.$roadmap->status->value)]);

        return back();
    }
}
