<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyVideoRequest;
use App\Jobs\VerifyVideoJob;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/** "Check now": queues an oEmbed check; the result shows on reload. */
class VideoVerificationController extends Controller
{
    public function __invoke(VerifyVideoRequest $request, Video $video): RedirectResponse
    {
        VerifyVideoJob::dispatch($video);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('videos.verifying')]);

        return back();
    }
}
