<?php

namespace App\Http\Requests\Admin;

use App\Models\Skill;

class ChangeSkillStatusRequest extends ChangeStatusRequest
{
    public function subject(): Skill
    {
        $skill = $this->route('skill');

        return $skill instanceof Skill ? $skill : abort(404);
    }
}
