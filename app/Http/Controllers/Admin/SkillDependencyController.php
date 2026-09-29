<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\SyncSkillDependencies;
use App\Domain\Curriculum\Graph\CycleDetected;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncSkillDependenciesRequest;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SkillDependencyController extends Controller
{
    public function __invoke(SyncSkillDependenciesRequest $request, Skill $skill, SyncSkillDependencies $action): RedirectResponse
    {
        try {
            $action->handle($skill, $request->dependencies());
        } catch (CycleDetected $cycle) {
            $names = Skill::query()->whereKey($cycle->path)->pluck('name', 'id');

            return back()->withErrors([
                'dependencies' => __('cms.dependency_cycle', [
                    'path' => collect($cycle->path)->map(fn (int|string $id) => $names[$id] ?? $id)->implode(' → '),
                ]),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.dependencies_saved')]);

        return back();
    }
}
