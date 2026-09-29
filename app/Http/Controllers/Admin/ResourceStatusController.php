<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\ChangeContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeResourceStatusRequest;
use App\Models\ExternalResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ResourceStatusController extends Controller
{
    public function __invoke(ChangeResourceStatusRequest $request, ExternalResource $resource, ChangeContentStatus $action): RedirectResponse
    {
        $action->handle($resource, $request->target());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.resource_status.'.$resource->status->value)]);

        return back();
    }
}
