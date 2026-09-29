<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\ReorderModules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderModulesRequest;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ModuleOrderController extends Controller
{
    public function __invoke(ReorderModulesRequest $request, Track $track, ReorderModules $action): RedirectResponse
    {
        $action->handle($track, $request->moduleIds());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.order_saved')]);

        return back();
    }
}
