<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Curriculum\Actions\ChangeContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeSkillStatusRequest;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SkillStatusController extends Controller
{
    public function __invoke(ChangeSkillStatusRequest $request, Skill $skill, ChangeContentStatus $action): RedirectResponse
    {
        $action->handle($skill, $request->target());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('cms.skill_status.'.$skill->status->value)]);

        return back();
    }
}
