<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Skill;
use App\Models\User;
use App\Policies\Concerns\AuthorizesContentEdits;
use Illuminate\Auth\Access\Response;

class SkillPolicy
{
    use AuthorizesContentEdits;

    /** Learners see published skills; drafts answer 404. */
    public function view(User $user, Skill $model): Response
    {
        return $model->isPublished() ? Response::allow() : Response::denyAsNotFound();
    }

    /** The CMS skill list (admin.access is checked by the route group). */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ContentViewAny->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ContentCreate->value);
    }

    public function update(User $user, Skill $model): Response
    {
        return $this->editRule($user, $model->created_by);
    }

    public function changeStatus(User $user, Skill $model, ContentStatus $to): bool
    {
        return $this->statusRule($user, $model->status, $to);
    }
}
