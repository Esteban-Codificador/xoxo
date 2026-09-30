<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\ChangeContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeVideoStatusRequest;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class VideoStatusController extends Controller
{
    public function __invoke(ChangeVideoStatusRequest $request, Video $video, ChangeContentStatus $action): RedirectResponse
    {
        $action->handle($video, $request->target());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('videos.status.'.$video->status->value)]);

        return back();
    }
}
