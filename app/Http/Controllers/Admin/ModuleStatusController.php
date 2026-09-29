<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\ChangeContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeModuleStatusRequest;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ModuleStatusController extends Controller
{
    public function __invoke(ChangeModuleStatusRequest $request, Module $module, ChangeContentStatus $action): RedirectResponse
    {
        $action->handle($module, $request->target());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.module_status.'.$module->status->value, ['module' => $module->title])]);

        return back();
    }
}
