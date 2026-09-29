<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\UpdateModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateModuleRequest;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ModuleController extends Controller
{
    public function update(UpdateModuleRequest $request, Module $module, UpdateModule $action): RedirectResponse
    {
        /** @var array{title: string, slug: string, summary: string} $data */
        $data = $request->validated();
        $action->handle($module, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.module_saved', ['module' => $module->title])]);

        return back();
    }
}
