<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\ChangeContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeTrackStatusRequest;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TrackStatusController extends Controller
{
    public function __invoke(ChangeTrackStatusRequest $request, Track $track, ChangeContentStatus $action): RedirectResponse
    {
        $action->handle($track, $request->target());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.track_status.'.$track->status->value)]);

        return back();
    }
}
