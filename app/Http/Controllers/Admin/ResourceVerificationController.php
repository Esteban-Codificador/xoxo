<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyResourceRequest;
use App\Jobs\VerifyResourceLinkJob;
use App\Models\ExternalResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/** "Verify now": queues a check of the URL; the result shows on reload. */
class ResourceVerificationController extends Controller
{
    public function __invoke(VerifyResourceRequest $request, ExternalResource $resource): RedirectResponse
    {
        VerifyResourceLinkJob::dispatch($resource);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.resource_verifying')]);

        return back();
    }
}
